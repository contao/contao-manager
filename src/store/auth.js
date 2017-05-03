/* eslint-disable no-param-reassign */

import api from '../api';

export default {
    namespaced: true,

    state: {
        username: null,
    },

    mutations: {
        setLogin(state, username) {
            state.username = username;
        },
        setLogout(state) {
            state.username = null;
        },
    },

    actions: {
        createAccount(store, { username, password }) {
            return api.login(username, password);
        },
        login({ dispatch }, { username, password }) {
            return api.login(username, password).then(
                () => dispatch('fetchStatus', true, { root: true }).then(() => true),
            );
        },
        logout({ dispatch }) {
            return api.logout().then(
                () => dispatch('fetchStatus', true, { root: true }).then(() => true),
            );
        },
    },
};
