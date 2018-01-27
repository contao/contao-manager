/* eslint-disable no-param-reassign */

import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        username: null,
    },

    mutations: {
        setUsername(state, username) {
            state.username = username;
        },
    },

    actions: {

        status({ commit }) {
            return Vue.http.get('api/session').then(
                (response) => {
                    commit('setUsername', (response.body && response.body.username) ? response.body.username : null);

                    return response.status;
                },
                (response) => {
                    commit('setUsername', null);

                    return response.status;
                },
            );
        },

        login({ commit }, { username, password }) {
            return Vue.http.post('api/session', { username, password }).then(
                (response) => {
                    commit('setUsername', response.username);

                    return true;
                },
                () => false,
            );
        },

        logout({ commit }) {
            return Vue.http.delete('api/session').then(
                () => {
                    commit('setUsername', null);

                    return true;
                },
                () => false,
            );
        },
    },
};
