/* eslint-disable no-param-reassign */

import api from '../api';

export default {
    namespaced: true,

    state: {
        isLoggedIn: false,
        username: null,
    },

    mutations: {
        setLogin(state, username) {
            state.isLoggedIn = true;
            state.username = username;
        },
        setLogout(state) {
            state.isLoggedIn = false;
            state.username = null;
        },
    },

    actions: {
        login: ({ dispatch }, { username, password }) => api.login(username, password).then(
            () => dispatch('fetchStatus', true, { root: true }).then(() => true),
        ),
        logout: ({ dispatch }) => api.logout().then(
            () => dispatch('fetchStatus', true, { root: true }).then(() => true),
        ),
    },
};
