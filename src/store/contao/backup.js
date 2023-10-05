import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        cache: null,
        loading: false,
        supported: false,
        files: [],
    },

    mutations: {
        setLoading(state, value) {
            state.loading = !!value;
        },

        setCache(state, response) {
            state.cache = response;
            state.loading = false;
            state.supported = response ? false : null;
            state.files = [];

            if (response && response.status === 200) {
                state.supported = true;
                state.files = response.data
            }
        },
    },

    actions: {
        fetch({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return new Promise((resolve) => {
                    resolve(state.cache);
                });
            }

            const handle = (response) => {
                commit('setCache', response);

                return response;
            };

            commit('setLoading', true);

            return Vue.http.get('api/contao/backup').then(handle, handle);
        }
    },
};
