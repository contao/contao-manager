/* eslint-disable no-param-reassign */

import Vue from 'vue';

const handle = (request, { commit }) => new Promise((resolve, reject) => {
    request.then(
        (response) => {
            commit('setCache', response.body['enabled']);
            commit('setIsEnabled', response.body['enabled'] === true);

            resolve(response.body['enabled']);
        },
        () => {
            commit('setIsEnabled', false);

            reject();
        },
    );
});

export default {
    namespaced: true,

    state: {
        cache: null,
        isEnabled: null,
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
        isEnabled(store, cache = true) {
            if (cache && store.state.cache) {
                return Promise.resolve(store.state.cache);
            }

            if (store.rootState.safeMode) {
                return Promise.reject();
            }

            return handle(Vue.http.get('api/contao/maintenance-mode'), store);
        },

        enable(store) {
            return handle(Vue.http.put('api/contao/maintenance-mode'), store);
        },

        disable(store) {
            return handle(Vue.http.delete('api/contao/maintenance-mode'), store);
        },
    },
};
