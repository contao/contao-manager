import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
    },

    getters: {
        users: state => state.cache || null,
    },

    mutations: {
        setCache(state, value) {
            state.cache = value;
        },
    },

    actions: {
        async get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return new Promise((resolve) => {
                    resolve(state.cache);
                });
            }

            const response = await axios.get('api/users');

            commit('setCache', response.data);

            return response.data;
        },

        async invite(store, scope) {
            try {
                return await axios.post('api/invitations', { scope });
            } catch (error) {
                return error.response;
            }
        },

        async delete(store, username) {
            return await axios.delete(`api/users/${username}`);
        }
    },
};
