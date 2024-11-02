/* eslint-disable no-param-reassign */

import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
        phpVersion: null,
        phpVersionId: null,
    },

    mutations: {
        setCache(state, response) {
            state.cache = response;
            state.phpVersion = null;
            state.phpVersionId = null;

            if (response && response.status === 200) {
                state.phpVersion = response.data.version;
                state.phpVersionId = response.data.version_id;
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

                return Promise.resolve(response);
            }

            return axios.get('api/server/php-web').then(handle, handle);
        },
    },
};
