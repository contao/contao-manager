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

        get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return new Promise((resolve) => {
                    resolve(state.cache);
                });
            }

            return Vue.http.get('api/server/config').then(
                response => response.body,
            ).then((result) => {
                commit('setCache', result);

                return result;
            });
        },

        set({ commit }, config) {
            return Vue.http.put('api/server/config', config).then(
                response => response.body,
            ).then((result) => {
                commit('setCache', result);

                return result;
            });
        },

    },
};
