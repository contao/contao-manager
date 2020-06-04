/* eslint-disable no-param-reassign */

import Vue from 'vue';

import details from 'contao-package-list/src/store/packages/details';
import features from 'contao-package-list/src/store/packages/features';
import uploads from './packages/uploads';

const hiddenPackages = ['contao/core-bundle', 'contao/installation-bundle', 'contao/conflicts'];
const isCountable = (name) => name.includes('/') && !hiddenPackages.includes(name);
const isVisible = (name, getters) => isCountable(name) && !getters.packageFeature(name);

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
    },

    getters: {
        packageInstalled: (state, g) => name => Object.keys(state.installed).includes(name) && !g.packageMissing(name),
        versionInstalled: state => (name, version) => Object.keys(state.installed).includes(name) && state.installed[name].version === version,
        packageRoot: state => name => Object.keys(state.root.require).includes(name),
        packageRequired: state => name => Object.keys(state.required).includes(name) && !!state.required[name].constraint,
        packageMissing: state => name => Object.keys(state.required).includes(name) && !state.required[name].constraint,
        packageAdded: state => name => Object.keys(state.add).includes(name),
        packageChanged: state => name => Object.keys(state.change).includes(name),
        packageUpdated: state => name => state.update.includes(name),
        packageRemoved: state => name => state.remove.includes(name),
        packageFeatures: () => name => features[name] ? features[name] : [],
        packageFeature: (s, g) => name => !!Object.keys(features).find((pkg) => features[pkg].includes(name) && (g.packageInstalled(pkg) || g.packageRequired(pkg))),
        packageVisible: (s, g) => name => isVisible(name, g),
        packageSuggested: state => name => !!Object.values(state.local).find(
            pkg => ((pkg.type.substr(0, 7) === 'contao-' || pkg.name.substr(0, 7) === 'contao/') && pkg.suggest && pkg.suggest.hasOwnProperty(name))
        ),

        totalChanges: state => Object.keys(state.add).filter(isCountable).length
            + Object.keys(state.required).filter(isCountable).length
            + Object.keys(state.change).filter(isCountable).length
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
    },

    actions: {
        async metadata({ state, dispatch }, name) {
            const metadata = await dispatch('algolia/getPackage', name, { root: true });
            const local = state.installed[name];

            if (!local) {
                return metadata;
            }

            if (!metadata) {
                return local;
            }

            const data = Object.assign(
                {},
                metadata,
                {
                    dependents: local.dependents,
                    conflict: local.conflict,
                    require: local.require,
                    'require-dev': local['require-dev'],
                    suggest: {},
                },
            );

            if (local.suggest) {
                Object.keys(local.suggest).forEach(k => {
                    data.suggest[k] = metadata.suggest && metadata.suggest[k] || local.suggest[k];
                });
            }

            return data;
        },

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

            Object.keys(features).forEach((pkg) => {
                features[pkg].forEach((feature) => {
                    if (Object.keys(state.root.require).includes(feature) || Object.keys(state.installed).includes(feature)) {
                        if (update.includes(pkg)) {
                            update.push(feature);
                        }

                        if (require[pkg]) {
                            require[feature] = require[pkg];
                        } else if (remove.includes(pkg)) {
                            remove.push(feature);
                        }

                        return;
                    }

                    if (!require.hasOwnProperty(feature)) {
                        return;
                    }

                    if (!update.includes(feature)) {
                        update.push(feature);
                    }

                    // Feature was added, make sure it's the same version as the parent
                    if (!require.hasOwnProperty(pkg) && state.root.require[pkg]) {
                        require[feature] = state.root.require[pkg];
                    } else if (require.hasOwnProperty(pkg)) {
                        require[feature] = require[pkg];
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
                if (!isVisible(name, getters)) {
                    return;
                }

                commit('update', name);
            });
        },
    },
};
