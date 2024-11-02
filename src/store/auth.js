/* eslint-disable no-param-reassign,no-fallthrough */

import axios from 'axios';
import views from '../router/views';
import LogoutWarning from "../components/fragments/LogoutWarning";

let timer;
let countdown;
let expires;
let $store;

const startCountdown = function () {
    clearTimeout(timer);

    expires = (Date.now() + 30 * 60 * 1000);
    countdown = 30 * 60;

    timer = setInterval(runCountdown, 1000);

    $store.commit('setCountdown', countdown);
    $store.commit('modals/close', 'logout-warning', { root: true });
};

const stopCountdown = function () {
    expires = null;
    countdown = null;
    clearInterval(timer);
    timer = undefined;

    $store.commit('setCountdown', countdown);
    $store.commit('modals/close', 'logout-warning', { root: true });
};

const runCountdown = function () {
    if (countdown > 0) {
        countdown = Math.floor(Math.max(expires - Date.now(), 0) / 1000);
    }

    if (countdown <= (5 * 60)) {
        $store.commit('modals/open', { id: 'logout-warning', component: LogoutWarning, priority: 255 }, { root: true });
    }

    if (countdown === 0) {
        $store.dispatch('logout');
        clearInterval(timer);
    }

    $store.commit('setCountdown', countdown);
};

export default {
    namespaced: true,

    state: {
        username: null,
        roles: null,
        countdown: null,
    },

    getters: {
        isGranted: state => role => {
            const roles = [];
            state.roles?.forEach(role => {
                // noinspection FallThroughInSwitchStatementJS
                switch (role) {
                    case 'ROLE_ADMIN':
                        roles.push('ROLE_ADMIN');
                    case 'ROLE_INSTALL':
                        roles.push('ROLE_INSTALL');
                    case 'ROLE_UPDATE':
                        roles.push('ROLE_UPDATE');
                    case 'ROLE_READ':
                        roles.push('ROLE_READ');
                }
            })

            return roles.includes(role);
        }
    },

    mutations: {
        setUser(state, data) {
            state.username = data?.username || null;
            state.roles = data?.roles || null;
        },

        setCountdown(state, value) {
            state.countdown = value;
        },

        renewCountdown() {
            if ($store) {
                startCountdown();
            }
        },

        resetCountdown() {
            if ($store) {
                stopCountdown();
            }
        },
    },

    actions: {
        status(store) {
            $store = store;

            return axios.get('api/session').then(
                (response) => {
                    if (response.data.username) {
                        store.commit('setUser', response.data);
                        startCountdown();
                    } else {
                        store.commit('setUser', null);
                        stopCountdown();
                    }

                    return response.status;
                },
                (response) => {
                    store.commit('setUser', null);
                    stopCountdown();

                    if (response.status === 403) {
                        store.commit('setLocked', null, { root: true });
                    }

                    return response.status;
                },
            );
        },

        login(store, { username, password }) {
            $store = store;

            return axios.post('api/session', { username, password }).then(
                (response) => {
                    store.commit('setUser', response.data);
                    startCountdown();

                    return true;
                },
                (response) => {
                    if (response.status === 403) {
                        store.commit('setLocked', null, { root: true });
                    }

                    return false;
                },
            );
        },

        logout({ commit }) {
            return axios.delete('api/session').then(
                () => true,
                response => (response.status === 401),
            ).then((result) => {
                if (result) {
                    commit('setUser', null);
                    commit('setView', views.LOGIN, { root: true });
                    stopCountdown();
                }

                return result;
            });
        },
    },
};
