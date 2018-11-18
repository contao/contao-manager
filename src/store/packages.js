/* eslint-disable no-param-reassign */

import Vue from 'vue';
import algoliasearch from 'algoliasearch';

const algolia = ({ state, commit }) => {
    let index = state.algolia;

    if (!index) {
        index = algoliasearch('60DW2LJW0P', 'e6efbab031852e115032f89065b3ab9f').initIndex(`v2_${Vue.i18n.locale()}`);
        commit('setAlgolia', index);
    }

    return index;
};

export default {
    namespaced: true,

    state: {
        algolia: null,
        packages: null,
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

        isInstalled(state, packageName) {
            return state.installed !== null
                && state.installed.find(pckg => pckg.name === packageName) !== undefined;
        },
    },

    mutations: {
        setPackages(state, packages) {
            state.packages = packages;
        },

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
        async load({ state, commit }, reload = false) {
            if (!reload && state.installed) {
                return state.installed;
            }

            commit('setInstalled', null);
            commit('reset');

            const data = {};
            const load = [
                Vue.http.get('api/packages/root'),
                Vue.http.get('api/packages/local'),
            ];
            const root = (await load[0]).body;
            const packages = (await load[1]).body;

            Object.keys(root.require).forEach((require) => {
                if (!require.includes('/')) {
                    return;
                }

                data[require] = {
                    name: require,
                    version: false,
                    constraint: root.require[require],
                };

                if (packages[require]) {
                    data[require] = Object.assign(data[require], packages[require]);
                }
            });

            commit('setPackages', packages);
            commit('setInstalled', data);

            return data;
        },

        fetch(store, name) {
            return new Promise((resolve, reject) => {
                algolia(store).getObject(name, (err, content) => {
                    if (err) {
                        reject(err);
                        return;
                    }

                    resolve(content);
                });
            });
        },

        search(store, value) {
            return new Promise((resolve, reject) => {
                algolia(store).search(value, (err, content) => {
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
