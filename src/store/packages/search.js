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
        metadata: {},
    },

    mutations: {
        setAlgolia(state, client) {
            state.algolia = client;
        },

        cache(state, pkg) {
            state.metadata[pkg.name] = pkg;
        }
    },

    actions: {
        async get(store, name) {
            if (Object.keys(store.state.metadata).includes(name)) {
                return store.state.metadata[name];
            }

            return new Promise((resolve, reject) => {
                algolia(store).getObject(name, (err, content) => {
                    const data = err ? null : content;

                    store.commit('cache', data);
                    resolve(data);
                });
            });
        },

        find(store, value) {
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
    },
};
