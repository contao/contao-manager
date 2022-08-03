/* eslint-disable no-param-reassign */

import Vue from 'vue';

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
                state.isLocked = response.body.locked === true;
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
            }

            return Vue.http.get('api/contao/install-tool/lock').then(handle, handle);
        },

        lock(store) {
            const handle = (response) => {
                store.commit('setCache', response);

                return Promise.resolve(response);
            }

            return Vue.http.put('api/contao/install-tool/lock').then(handle, handle);
        },

        unlock(store) {
            const handle = (response) => {
                store.commit('setCache', response);

                return Promise.resolve(response);
            }

            return Vue.http.delete('api/contao/install-tool/lock').then(handle, handle);
        },
    },
};
