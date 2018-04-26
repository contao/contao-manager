/* eslint-disable no-param-reassign */

import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        installed: null,
        add: {},
        change: {},
        update: [],
        remove: [],
    },

    mutations: {
        setInstalled(state, packages) {
            state.installed = packages;
        },

        add(state, pckg) {
            Vue.set(state.add, pckg.name, pckg);
        },

        change(state, { name, version }) {
            this.commit('packages/restore', name);
            Vue.set(state.change, name, version);
        },

        update(state, name) {
            this.commit('packages/restore', name);
            state.update.push(name);
        },

        updateAll(state) {
            Object.keys(state.installed).forEach((name) => {
                state.update.push(name);
            });
        },

        remove(state, name) {
            this.commit('packages/restore', name);
            state.remove.push(name);
        },

        restore(state, name) {
            Vue.delete(state.add, name);
            Vue.delete(state.change, name);

            if (state.remove.includes(name)) {
                state.remove.splice(state.remove.indexOf(name), 1);
            }

            if (state.update.includes(name)) {
                state.update.splice(state.update.indexOf(name), 1);
            }
        },

        reset(state) {
            state.add = {};
            state.change = {};
            state.update = [];
            state.remove = [];
        },
    },

    actions: {
        load({ state, commit }, reload = false) {
            if (!reload && state.installed) {
                return;
            }

            commit('setInstalled', null);
            commit('reset', null);

            Vue.http.get('api/packages').then(
                (response) => {
                    const packages = Object.assign(
                        {},
                        { 'contao/manager-bundle': response.body['contao/manager-bundle'] },
                        response.body,
                    );

                    commit('setInstalled', packages);
                },
            );
        },

        apply({ state, dispatch }, dryRun = false) {
            const require = state.change;
            const remove = state.remove;
            const update = state.update.concat(
                Object.keys(state.change),
                state.remove,
            );

            Object.keys(state.add).forEach((pkg) => {
                require[state.add[pkg].name] = state.add[pkg].constraint || null;
                update.push(state.add[pkg].name);
            });

            const task = {
                name: 'composer/update',
                config: {
                    require,
                    remove,
                    update,
                    dry_run: dryRun === true,
                },
            };

            dispatch('tasks/execute', task, { root: true }).then(
                () => {
                    dispatch('load', true);
                },
            );
        },
    },
};
