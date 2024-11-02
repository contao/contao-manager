/* eslint-disable no-param-reassign */

import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
    },

    mutations: {
        setCache(state, value) {
            state.cache = value;
        },
    },

    actions: {
        async get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return state.cache;
            }

            const result = (await axios.get('api/server/config')).data;
            commit('setCache', result);

            return result;
        },

        async set({ commit }, config) {
            try {
                const result = (await axios.put('api/server/config', config)).data;
                commit('setCache', result);

                return result;
            } catch (error) {
                if (!error.response) {
                    throw error;
                }

                return error.response.data;
            }
        },
    },
};
