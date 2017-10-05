/* eslint-disable no-param-reassign */

import Vue from 'vue';

import api from '../api';
import views from '../router/views';

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

        status: ({ commit }) => {
            api.fetchSession().then((statusCode) => {
                if (statusCode === 200) {
                    commit('setView', views.BOOT, { root: true });
                } else if (statusCode === 204) {
                    commit('setView', views.ACCOUNT, { root: true });
                } else if (statusCode === 401) {
                    commit('setView', views.LOGIN, { root: true });
                } else {
                    commit('setError', {
                        title: Vue.i18n.translate('ui.app.apiError'),
                        type: 'about:blank',
                        status: statusCode,
                    }, { root: true });
                }
            });
        },

        login({ commit }, { username, password }) {
            return api.login(username, password).then((result) => {
                if (result.success) {
                    commit('setLogin', result.username);
                }

                return result.success;
            });
        },

        logout({ commit }) {
            return api.logout().then((success) => {
                if (success) {
                    commit('setLogout');
                }

                return success;
            });
        },

        createAccount(store, { username, password }) {
            return api.login(username, password);
        },
    },
};
