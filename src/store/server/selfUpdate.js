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

            return Vue.http.get('api/server/self-update').then(
                response => response.body,
                (response) => {
                    if (response.status === 501) {
                        return {
                            current_version: null,
                            latest_version: null,
                            channel: 'dev',
                        };
                    }

                    throw response;
                },
            ).then((result) => {
                commit('setCache', result);

                return result;
            });
        },
    },
};
