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
        async get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return state.cache;
            }

            const content = (await axios.get('api/server/phpinfo', { responseType: 'text' })).data;
            commit('setCache', content);

            return content;
        },
    },
};
