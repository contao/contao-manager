import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
        loading: false,
        supported: false,
        files: [],

        // Enable backup restore if a theme was installed
        restore: false,
        restored: false,
    },

    getters: {
        hasBackups: state => state.supported && state.files && state.files.length > 0,
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

        setRestore(state, value) {
            state.restore = value;
        },

        setRestored(state) {
            state.restored = true;
        }
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

            return axios.get('api/contao/backup').then(handle, handle);
        }
    },
};
