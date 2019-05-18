/* eslint-disable no-param-reassign */

import Vue from 'vue';
import views from '../router/views';

let countdown;

export default {
    namespaced: true,

    state: {
        username: null,
        expires: null,
    },

    getters: {
        warnForLogout(state) {
            return state.expires !== null && state.expires <= (5 * 60);
        },
    },

    mutations: {
        setUsername(state, username) {
            state.username = username;
        },

        renewCountdown(state) {
            state.expires = 30 * 60;

            if (!countdown) {
                countdown = setInterval(() => {
                    this.commit('auth/countdown');
                }, 1000);
            }
        },

        resetCountdown(state) {
            state.expires = null;
            clearInterval(countdown);
            countdown = undefined;
        },

        countdown(state) {
            if (state.expires > 0) {
                state.expires = state.expires - 1;
            }

            if (state.expires === 0) {
                this.dispatch('auth/logout');
            }
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
                    commit('setUsername', response.body.username);

                    return true;
                },
                () => false,
            );
        },

        logout({ commit }) {
            return Vue.http.delete('api/session').then(
                () => {
                    commit('setUsername', null);
                    commit('setView', views.LOGIN, { root: true });
                    clearInterval(countdown);

                    return true;
                },
                () => false,
            );
        },
    },
};
