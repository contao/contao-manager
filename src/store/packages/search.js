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

        cache(state, { name, data }) {
            state.metadata[name] = data;
        },
    },

    actions: {
        async get(store, name) {
            if (Object.keys(store.state.metadata).includes(name)) {
                return store.state.metadata[name];
            }

            return new Promise((resolve) => {
                algolia(store).getObject(name, (err, content) => {
                    const data = {
                        name,
                        data: err ? null : content
                    };

                    store.commit('cache', data);
                    resolve(content);
                });
            });
        },

        find(store, params) {
            return new Promise((resolve, reject) => {
                algolia(store).search(
                    params,
                    (err, response) => {
                        if (err) {
                            reject(false);
                            return;
                        }

                        resolve(response);
                    },
                );
            });
        },
    },
};
