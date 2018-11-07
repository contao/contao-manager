/* eslint-disable no-param-reassign */

import Vue from 'vue';
import algoliasearch from 'algoliasearch';

export default {
    namespaced: true,

    state: {
        algolia: null,
        installed: null,
        add: {},
        change: {},
        update: [],
        remove: [],
    },

    getters: {
        totalChanges(state) {
            return Object.keys(state.add).length
                + Object.keys(state.change).length
                + state.update.length
                + state.remove.length;
        },
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

        setAlgolia(state, client) {
            state.algolia = client;
        },
    },

    actions: {
        load({ state, commit }, reload = false) {
            if (!reload && state.installed) {
                return new Promise((resolve) => { resolve(); });
            }

            commit('setInstalled', null);
            commit('reset', null);

            return Vue.http.get('api/packages').then(
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

        search({ state, commit }, value) {
            let index = state.algolia;

            if (!state.algolia) {
                index = algoliasearch('60DW2LJW0P', 'e6efbab031852e115032f89065b3ab9f').initIndex(`v2_${Vue.i18n.locale()}`);
                commit('setAlgolia', index);
            }

            return new Promise((resolve, reject) => {
                console.log(index);
                index.search(value, (err, content) => {
                    if (err) {
                        reject(false);
                        return;
                    }

                    if (content.nbHits === 0) {
                        resolve({});
                        return;
                    }

                    const packages = {};

                    content.hits.forEach((pkg) => {
                        packages[pkg.name] = pkg;
                    });

                    resolve(packages);
                });
            });
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

            return dispatch('tasks/execute', task, { root: true });
        },
    },
};
