/* eslint-disable no-param-reassign */

import Vue from 'vue';

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
                state.phpVersion = response.body.version;
                state.phpVersionId = response.body.version_id;
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

            return Vue.http.get('api/server/php-web').then(handle, handle);
        },
    },
};
