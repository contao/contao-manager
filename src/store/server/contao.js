/* eslint-disable no-param-reassign */

import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
        contaoVersion: null,
        contaoApi: null,
        contaoConfig: null,
    },

    getters: {
        badgeTitle: (state) => state.contaoConfig?.backend?.badge_title,
    },

    mutations: {
        setCache(state, response) {
            state.cache = response;
            state.contaoVersion = null;
            state.contaoApi = null;
            state.contaoConfig = null;

            if (response) {
                state.contaoVersion = response.data.version;
                state.contaoApi = response.data.api;
                state.contaoConfig = response.data.config;
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
                .get('api/server/contao')
                .then(handle)
                .catch((error) => handle(error.response));
        },

        documentRoot(store, { directory, usePublicDir = false }) {
            const params = {
                usePublicDir,
            };

            if (directory) {
                params.directory = directory;
            }

            return axios.post('api/server/contao', params).catch((response) => response);
        },
    },
};
