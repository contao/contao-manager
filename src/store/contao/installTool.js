/* eslint-disable no-param-reassign */

import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
        isSupported: null,
        isLocked: null,
    },

    mutations: {
        setCache(state, response) {
            state.cache = response;
            state.isSupported = response ? false : null;
            state.isLocked = null;

            if (response && response.status === 200) {
                state.isSupported = true;
                state.isLocked = response.data.locked === true;
            }
        },
    },

    actions: {
        fetch(store, cache = true) {
            if (cache && store.state.cache) {
                return Promise.resolve(store.state.cache);
            }

            if (store.rootState.safeMode) {
                store.commit('setCache');
                return Promise.resolve();
            }

            const handle = (response) => {
                store.commit('setCache', response);

                return Promise.resolve(response);
            };

            return axios
                .get('api/contao/install-tool/lock')
                .then(handle)
                .catch((error) => handle(error.response));
        },

        lock(store) {
            const handle = (response) => {
                store.commit('setCache', response);

                return Promise.resolve(response);
            };

            return axios
                .put('api/contao/install-tool/lock')
                .then(handle)
                .catch((error) => handle(error.response));
        },

        unlock(store) {
            const handle = (response) => {
                store.commit('setCache', response);

                return Promise.resolve(response);
            };

            return axios
                .delete('api/contao/install-tool/lock')
                .then(handle)
                .catch((error) => handle(error.response));
        },
    },
};
