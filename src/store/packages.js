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
        installed: null,
        required: {},
        add: {},
        change: {},
        update: [],
        remove: [],
    },

    getters: {
        hasAdded: state => Object.keys(state.add).length > 0 || Object.keys(state.required).length > 0,

        packageInstalled: state => name => Object.keys(state.installed).includes(name),
        packageRequired: state => name => Object.keys(state.required).includes(name),
        packageAdded: state => name => Object.keys(state.add).includes(name),
        packageChanged: state => name => Object.keys(state.change).includes(name),
        packageUpdated: state => name => state.update.includes(name),
        packageRemoved: state => name => state.remove.includes(name),

        totalChanges: state => Object.keys(state.add).length
            + Object.keys(state.required).length
            + Object.keys(state.change).length
            + state.update.length
            + state.remove.length
            - Object.values(state.change).filter(p => Object.keys(state.required).includes(p.name)).length
            - state.remove.filter(p => Object.keys(state.required).includes(p)).length,

        totalRequired: state => Object.keys(state.required).length
            - Object.values(state.change).filter(pkg => Object.keys(state.required).includes(pkg.name)).length
            - state.remove.filter(pkg => Object.keys(state.required).includes(pkg)).length,

        canResetChanges: (s, get) => get.totalChanges > get.totalRequired,
    },

    mutations: {
        setInstalled(state, packages) {
            if (packages === null) {
                state.installed = null;
                state.required = {};
                return;
            }

            const installed = {};
            const required = {};

            Object.keys(packages).forEach((name) => {
                if (packages[name].version === false) {
                    required[name] = packages[name];
                } else {
                    installed[name] = packages[name];
                }
            });

            state.installed = installed;
            state.required = required;
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
        async load({ state, commit }) {
            commit('setInstalled', null);
            commit('reset');

            const packages = {};
            const load = [
                Vue.http.get('api/packages/root'),
                Vue.http.get('api/packages/local'),
            ];
            const root = (await load[0]).body;
            const local = (await load[1]).body;

            Object.keys(root.require).forEach((require) => {
                if (!require.includes('/')) {
                    return;
                }

                packages[require] = {
                    name: require,
                    version: false,
                    constraint: root.require[require],
                };

                if (local[require]) {
                    packages[require] = Object.assign(packages[require], local[require]);
                }
            });

            commit('setInstalled', packages);

            return packages;
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
                Object.keys(state.required),
                Object.keys(state.change).filter(pkg => !Object.keys(state.required).includes(pkg)),
                state.remove.filter(pkg => !Object.keys(state.required).includes(pkg)),
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
