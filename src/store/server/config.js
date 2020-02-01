/* eslint-disable no-param-reassign */

import Vue from 'vue';

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

            const result = (await Vue.http.get('api/server/config')).body;
            commit('setCache', result);

            return result;
        },

        async set({ commit }, config) {
            const result = (await Vue.http.put('api/server/config', config)).body;
            commit('setCache', result);

            return result;
        },
    },
};
