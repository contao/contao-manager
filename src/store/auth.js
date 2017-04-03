/* eslint-disable no-param-reassign */

import Vue from 'vue';
import api from '../api';

export default {
    namespaced: true,

    state: {
        token: null,
        isLoggedIn: false,
        username: null,
        error: null,
    },

    mutations: {
        setLogin(state, { token, error, username }) {
            state.token = token;
            state.error = error;
            state.username = username;

            if (token !== null) {
                state.isLoggedIn = true;
                Vue.http.headers.common.Authorization = `Bearer ${token}`;
            } else {
                state.isLoggedIn = false;
                Vue.http.headers.common.Authorization = null;
            }
        },
        setLogout(state) {
            state.token = null;
            state.error = null;
            state.username = null;
            state.isLoggedIn = false;
            Vue.http.headers.common.Authorization = null;
        },
    },

    actions: {
        login: ({ commit, dispatch }, { username, password }) => api.login(username, password).then(
            (status) => {
                commit('setLogin', { token: status.token, error: status.error, username });

                if (status.token === null) {
                    return new Promise(resolve => resolve(false));
                }

                return dispatch('fetchStatus', true, { root: true }).then(() => true);
            },
        ),
        logout: ({ commit, dispatch }) => {
            commit('setLogout');

            return dispatch('fetchStatus', true, { root: true }).then(() => true);
        },
    },
};
