/* eslint-disable no-param-reassign */

import Vue from 'vue';

const handle = (request, { commit }) => new Promise((resolve, reject) => {
    request.then(
        (response) => {
            commit('setCache', response.body['locked']);
            commit('setIsLocked', response.body['locked'] === true);
            commit('setIsSupported', true);

            resolve(response.body['locked']);
        },
        () => {
            commit('setIsLocked', false);
            commit('setIsSupported', false);

            reject();
        },
    );
});

export default {
    namespaced: true,

    state: {
        cache: null,
        isLocked: null,
        isSupported: false,
    },

    mutations: {
        setCache(state, value) {
            state.cache = value;
        },

        setIsLocked(state, value) {
            state.isLocked = value;
        },

        setIsSupported(state, value) {
            state.isSupported = value;
        },
    },

    actions: {
        fetch(store, cache = true) {
            if (cache && store.state.cache) {
                return Promise.resolve(store.state.cache);
            }

            if (store.rootState.safeMode) {
                return Promise.reject();
            }

            return handle(Vue.http.get('api/contao/install-tool/lock'), store);
        },

        lock(store) {
            return handle(Vue.http.put('api/contao/install-tool/lock'), store);
        },

        unlock(store) {
            return handle(Vue.http.delete('api/contao/install-tool/lock'), store);
        },
    },
};
