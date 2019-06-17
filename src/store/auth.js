/* eslint-disable no-param-reassign */

import Vue from 'vue';
import views from '../router/views';

let countdown;

export default {
    namespaced: true,

    state: {
        username: null,
        expires: null,
        countdown: null,
    },

    getters: {
        warnForLogout(state) {
            return state.countdown !== null && state.countdown <= (5 * 60);
        },

        isExpired(state) {
            return state.countdown !== null && state.countdown <= 0;
        }
    },

    mutations: {
        setUsername(state, username) {
            state.username = username;
        },

        renewCountdown(state) {
            state.expires = (Date.now() + 30 * 60 * 1000);
            state.countdown = 30 * 60;

            if (!countdown) {
                countdown = setInterval(() => {
                    this.commit('auth/countdown');
                }, 1000);
            }
        },

        resetCountdown(state) {
            state.expires = null;
            state.countdown = null;
            clearInterval(countdown);
            countdown = undefined;
        },

        countdown(state) {
            if (state.countdown > 0) {
                state.countdown = Math.floor(Math.max(state.expires - Date.now(), 0) / 1000);
            }

            if (state.countdown === 0) {
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
                () => true,
                response => (response.status === 401),
            ).then((result) => {
                if (result) {
                    commit('setUsername', null);
                    commit('setView', views.LOGIN, { root: true });
                    clearInterval(countdown);
                    countdown = undefined;
                }

                return result;
            });
        },
    },
};
