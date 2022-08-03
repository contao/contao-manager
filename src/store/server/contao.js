/* eslint-disable no-param-reassign */

import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        cache: null,
        contaoVersion: null,
        contaoApi: null,
    },

    mutations: {
        setCache(state, response) {
            state.cache = response;
            state.contaoVersion = null;
            state.contaoApi = null;

            if (response) {
                state.contaoVersion = response.body.version;
                state.contaoApi = response.body.api;
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

            return Vue.http.get('api/server/contao').then(handle, handle);
        },

        documentRoot(store, { directory, usePublicDir = false }) {
            const params = {
                usePublicDir
            };

            if (directory) {
                params.directory = directory;
            }

            return Vue.http.post('api/server/contao', params).catch(response => response);
        }
    },
};
