/* eslint-disable no-param-reassign */

import Vue from 'vue';

const handle = (request, { commit }) => new Promise((resolve, reject) => {
    request.then(
        (response) => {
            commit('setCache', response.body['access-key']);
            commit('setIsEnabled', response.body['access-key'] !== '');

            resolve(response.body['access-key']);
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
        get(store, cache = true) {
            if (cache && store.state.cache) {
                return Promise.resolve(store.state.cache);
            }

            if (store.rootState.safeMode
                || store.rootState.server.contao.contaoApi.version < 1
                || !store.rootState.server.contao.contaoApi.features?.['contao/manager-bundle']?.['dot-env']?.includes('APP_DEV_ACCESSKEY')
            ) {
                return Promise.reject();
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
