/* eslint-disable no-param-reassign */

import Vue from 'vue';

const handle = (request, { commit }) => request.then(
    response => response.body['access-key'],
    () => '',
).then((result) => {
    commit('setCache', result);
    commit('setIsEnabled', result !== '');

    return result;
});

export default {
    namespaced: true,

    state: {
        cache: null,
        isEnabled: false,
    },

    mutations: {
        setCache(state, value) {
            state.cache = value;
        },
        setIsEnabled(state, value) {
            state.isEnabled = value;
        },
    },

    actions: {

        get(store, cache = true) {
            if (cache && store.state.cache) {
                return new Promise((resolve) => {
                    resolve(store.state.cache);
                });
            }

            return handle(Vue.http.get('api/contao/access-key'), store);
        },

        set(store, payload) {
            return handle(Vue.http.put('api/contao/access-key', payload), store);
        },

        delete(store) {
            return handle(Vue.http.delete('api/contao/access-key'), store);
        },

    },
};
