import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
        supported: false,
        hasUser: null,
    },

    mutations: {
        setCache(state, response) {
            state.cache = response;
            state.supported = false;
            state.hasUser = null;

            if (response && (response.status === 200 || response.status === 201)) {
                state.supported = true;
                state.hasUser = !!response.data.hasUser;
            }
        },
    },

    actions: {
        get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return new Promise((resolve) => {
                    resolve(state.cache);
                });
            }

            const handle = (response) => {
                commit('setCache', response);

                return response;
            };

            return axios
                .get('api/server/admin-user')
                .then(handle)
                .catch((error) => handle(error.response));
        },

        set({ commit }, data) {
            const handle = (response) => {
                commit('setCache', response);

                return response;
            };

            return axios
                .post('api/server/admin-user', data)
                .then(handle)
                .catch((error) => handle(error.response));
        },
    },
};
