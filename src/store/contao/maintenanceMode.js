/* eslint-disable no-param-reassign */

import axios from 'axios';

const handle = (request, { commit }) =>
    new Promise((resolve, reject) => {
        request
            .then((response) => {
                commit('setCache', response.data.enabled);
                commit('setIsEnabled', response.data.enabled === true);

                resolve(response.data.enabled);
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
        isEnabled(store, cache = true) {
            if (cache && store.state.cache) {
                return Promise.resolve(store.state.cache);
            }

            if (store.rootState.safeMode) {
                return Promise.reject();
            }

            return handle(axios.get('api/contao/maintenance-mode'), store);
        },

        enable(store) {
            return handle(axios.put('api/contao/maintenance-mode'), store);
        },

        disable(store) {
            return handle(axios.delete('api/contao/maintenance-mode'), store);
        },
    },
};
