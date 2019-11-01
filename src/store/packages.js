/* eslint-disable no-param-reassign */

import Vue from 'vue';

import details from 'contao-package-list/src/store/packages/details';
import uploads from './packages/uploads';

const isVisible = (name, getters) => name.includes('/') && name !== 'contao/manager-bundle' && name !== 'contao/conflicts' && !getters.isFeature(name);

export default {
    namespaced: true,

    modules: {
        details,
        uploads,
    },

    state: {
        root: null,
        local: null,
        installed: null,
        required: {},
        add: {},
        change: {},
        update: [],
        remove: [],
        features: {},
    },

    getters: {
        packageInstalled: (state, g) => name => Object.keys(state.installed).includes(name) && !g.packageMissing(name),
        versionInstalled: state => (name, version) => Object.keys(state.installed).includes(name) && state.installed[name].version === version,
        packageRequired: state => name => Object.keys(state.required).includes(name) && state.required[name].constraint,
        packageMissing: state => name => Object.keys(state.required).includes(name) && !state.required[name].constraint,
        packageAdded: state => name => Object.keys(state.add).includes(name),
        packageChanged: state => name => Object.keys(state.change).includes(name),
        packageUpdated: state => name => state.update.includes(name),
        packageRemoved: state => name => state.remove.includes(name),

        totalChanges: state => Object.keys(state.add).length
            + Object.keys(state.required).length
            + Object.keys(state.change).length
            + state.update.length
            + state.remove.length
            - Object.values(state.add).filter(p => Object.keys(state.required).includes(p.name)).length
            - Object.values(state.change).filter(p => Object.keys(state.required).includes(p.name)).length
            - state.remove.filter(p => Object.keys(state.required).includes(p)).length,

        totalRequired: state => Object.keys(state.required).length
            - Object.values(state.add).filter(pkg => Object.keys(state.required).includes(pkg.name)).length
            - Object.values(state.change).filter(pkg => Object.keys(state.required).includes(pkg.name)).length
            - state.remove.filter(pkg => Object.keys(state.required).includes(pkg)).length,

        canResetChanges: (s, get) => get.totalChanges > get.totalRequired,

        isSuggested: state => name => !!Object.values(state.local).find(pkg => (pkg.type.substr(0, 7) === 'contao-' && pkg.suggest && pkg.suggest.hasOwnProperty(name))),
        isFeature: (s, g) => (name) => !!Object.keys(s.features).find((pkg) => s.features[pkg].includes(name) && (g.packageInstalled(pkg) || g.packageRequired(pkg))),

        visibleRequired: (s, g) => Object.values(s.required).filter(pkg => isVisible(pkg.name, g)),
        visibleInstalled: (s, g) => Object.values(g.installed).filter(pkg => isVisible(pkg.name, g)),
        visibleAdded: (s, g) => Object.values(s.add).filter(pkg => isVisible(pkg.name, g)),

        installed: (state) => {
            if (!state.root || !state.installed) {
                return {};
            }

            const packages = {};

            Object.keys(state.root.require).forEach((require) => {
                if (!require.includes('/')) {
                    return;
                }

                if (state.installed[require]) {
                    packages[require] = {
                        name: require,
                        version: false,
                        constraint: state.root.require[require],
                    };

                    packages[require] = Object.assign(packages[require], state.installed[require]);
                }
            });

            return packages;
        },
    },

    mutations: {
        setInstalled(state, { root, local: packages, missing }) {
            const installed = {};
            const required = {};

            Object.keys(packages).forEach((name) => {
                if (packages[name].version === false) {
                    required[name] = packages[name];
                } else {
                    installed[name] = packages[name];
                }
            });

            Object.keys(root.require).forEach((name) => {
                if (!name.includes('/')) {
                    return;
                }

                if (!installed.hasOwnProperty(name) && !required.hasOwnProperty(name)) {
                    required[name] = { name, constraint: root.require[name] };
                }
            });

            if (missing) {
                missing.forEach((name) => {
                    required[name] = { name, constraint: null };
                });
            }

            state.root = root;
            state.local = packages;
            state.installed = installed;
            state.required = required;
        },

        clearInstalled(state) {
            state.root = null;
            state.local = null;
            state.installed = null;
            state.required = {};
        },

        add(state, pkg) {
            Vue.set(state.add, pkg.name, pkg);
        },

        change(state, { name, version }) {
            this.commit('packages/restore', name);
            Vue.set(state.change, name, version);
        },

        update(state, name) {
            this.commit('packages/restore', name);
            state.update.push(name);
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

        pushFeatures(state, features) {
            Object.keys(features).forEach((name) => {
                Vue.set(state.features, name, features[name]);
            });
        },
    },

    actions: {
        async load({ commit }) {
            commit('clearInstalled');
            commit('reset');

            const packages = {};
            const load = [
                Vue.http.get('api/packages/root'),
                Vue.http.get('api/packages/local'),
                Vue.http.get('api/packages/missing'),
            ];

            commit('setInstalled', {
                root: (await load[0]).body,
                local: (await load[1]).body,
                missing: (await load[2]).body
            });

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

            Object.keys(state.features).forEach((pkg) => {
                state.features[pkg].forEach((feature) => {
                    if (Object.keys(state.root.require).includes(feature)) {
                        if (update.includes(pkg)) {
                            update.push(feature);
                        }

                        if (require[pkg]) {
                            require[feature] = require[pkg];
                        } else if (remove.includes(pkg)) {
                            remove.push(feature);
                        }
                    }

                    // Feature was added, make sure it's the same version as the parent
                    if (require.hasOwnProperty(feature) && !require.hasOwnProperty(pkg) && state.root.require[pkg]) {
                        require[feature] = state.root.require[pkg];
                    }
                });
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

        updateAll({ state, getters, commit }) {
            Object.keys(state.root.require).forEach((name) => {
                if (name !== 'contao/manager-bundle' && !isVisible(name, getters)) {
                    return;
                }

                commit('update', name);
            });
        },
    },
};
