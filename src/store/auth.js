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

        status: ({ commit }) => {
            const handleSession = ({ statusCode, username }) => {
                if (username) {
                    commit('setLogin', username);
                } else {
                    commit('setLogout');
                }

                return statusCode;
            };

            return api.session.get().then(
                handleSession,
                handleSession,
            );
        },

        login({ commit }, { username, password }) {
            return api.session.create(username, password).then((result) => {
                if (result.success) {
                    commit('setLogin', result.username);
                }

                return result.success;
            });
        },

        logout({ commit }) {
            return api.session.delete().then((success) => {
                if (success) {
                    commit('setLogout');
                }

                return success;
            });
        },
    },
};
