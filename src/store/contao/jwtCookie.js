/* eslint-disable no-param-reassign */

import axios from 'axios';

const handle = (request, { commit }) =>
    new Promise((resolve, reject) => {
        request
            .then((response) => {
                commit('setCache', response.data);
                commit('setIsDebugEnabled', response.status !== 204 && response.data.debug);

                resolve(response.data);
            })
            .catch(() => {
                commit('setIsDebugEnabled', false);

                reject();
            });
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

            if (
                store.rootState.safeMode ||
                store.rootState.server.contao.contaoApi.version < 2 ||
                !store.rootState.server.contao.contaoApi.features?.['contao/manager-bundle']?.['jwt-cookie']?.includes('debug')
            ) {
                return Promise.reject();
            }

            return handle(axios.get('api/contao/jwt-cookie'), store);
        },

        enableDebug(store) {
            return handle(axios.put('api/contao/jwt-cookie', { debug: true }), store);
        },

        disableDebug(store) {
            return handle(axios.put('api/contao/jwt-cookie', { debug: false }), store);
        },

        delete(store) {
            return handle(axios.delete('api/contao/jwt-cookie'), store);
        },
    },
};
