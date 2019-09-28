/* eslint-disable no-param-reassign */

import Vue from 'vue';

const handle = (request, { commit }) => new Promise((resolve, reject) => {
    request.then(
        (response) => {
            commit('setCache', response.body);
            commit('setIsDebugEnabled', response.status !== 204 && response.data.debug);

            resolve(response.body);
        },
        () => {
            commit('setIsDebugEnabled', false);

            reject();
        },
    );
});

export default {
    namespaced: true,

    state: {
        cache: null,
        isDebugEnabled: null,
    },

    mutations: {
        setCache(state, value) {
            state.cache = value;
        },

        setIsDebugEnabled(state, value) {
            state.isDebugEnabled = value;
        },
    },

    actions: {
        get(store, cache = true) {
            if (cache && store.state.cache) {
                return Promise.resolve(store.state.cache);
            }

            if (store.rootState.safeMode) {
                return Promise.reject();
            }

            return handle(Vue.http.get('api/contao/jwt-cookie'), store);
        },

        enableDebug(store) {
            return handle(Vue.http.put('api/contao/jwt-cookie', { debug: true }), store);
        },

        disableDebug(store) {
            return handle(Vue.http.put('api/contao/jwt-cookie', { debug: false }), store);
        },

        delete(store) {
            return handle(Vue.http.delete('api/contao/jwt-cookie'), store);
        },
    },
};
