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

            const handle = (response) => {
                commit('setCache', response);

                return response;
            };

            return Vue.http.get('api/server/contao').then(handle, handle);
        },

        documentRoot(store, { directory, usePublicDir = false }) {
            return Vue.http.post('api/server/contao', {
                directory,
                usePublicDir
            }).catch(response => response);
        }
    },
};
