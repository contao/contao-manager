/* eslint-disable no-param-reassign */

import axios from 'axios';

const handle = (request, { commit }) =>
    new Promise((resolve, reject) => {
        request
            .then((response) => {
                commit('setCache', response.data['access-key']);
                commit('setIsEnabled', response.data['access-key'] !== '');

                resolve(response.data['access-key']);
            })
            .catch(() => {
                commit('setIsEnabled', false);

                reject();
            });
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

            if (
                store.rootState.safeMode ||
                store.rootState.server.contao.contaoApi.version < 1 ||
                !store.rootState.server.contao.contaoApi.features?.['contao/manager-bundle']?.['dot-env']?.includes('APP_DEV_ACCESSKEY')
            ) {
                return Promise.reject();
            }

            return handle(axios.get('api/contao/access-key'), store);
        },

        set(store, payload) {
            return handle(axios.put('api/contao/access-key', payload), store);
        },

        delete(store) {
            return handle(axios.delete('api/contao/access-key'), store);
        },
    },
};
