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

            const content = (await Vue.http.get('api/server/phpinfo')).bodyText;
            commit('setCache', content);

            return content;
        },

    },
};
