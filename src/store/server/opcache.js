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
        get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return new Promise((resolve) => {
                    resolve(state.cache);
                });
            }

            return axios
                .get('api/server/opcache')
                .then((response) => response.data)
                .then((result) => {
                    commit('setCache', result);

                    return result;
                });
        },

        delete({ commit }, token) {
            return axios
                .delete(`api/server/opcache?opcache_reset=${encodeURIComponent(token)}`)
                .then((response) => response.data)
                .then((result) => {
                    commit('setCache', result);

                    return result;
                });
        },
    },
};
