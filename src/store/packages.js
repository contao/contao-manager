/* eslint-disable no-param-reassign */

import Vue from 'vue';

import search from './packages/search';
import uploads from './packages/uploads';

const filterInvisiblePackages = (pkg) => {
    return pkg.name !== 'contao/manager-bundle' && pkg.name !== 'contao/conflicts';
};

export default {
    namespaced: true,

    modules: {
        search,
        uploads,
    },

    state: {
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

        visibleInstalled: s => Object.values(s.installed).filter(filterInvisiblePackages),
        visibleRequired: s => Object.values(s.required).filter(filterInvisiblePackages),
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

        add(state, pkg) {
            if (!state.add[pkg.name] && !state.required[pkg.name]) {
                Vue.set(state.add, pkg.name, pkg);
            }
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
        async load({ commit }) {
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
                    uploads: true,
                    dry_run: dryRun === true,
                },
            };

            return dispatch('tasks/execute', task, { root: true });
        },
    },
};
