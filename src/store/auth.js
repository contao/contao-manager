/* eslint-disable no-param-reassign,no-fallthrough */

import axios from 'axios';
import request from '../tools/request';
import scopes from '../scopes';
import views from '../router/views';
import LogoutWarning from '../components/fragments/LogoutWarning';

let timer;
let countdown;
let expires;
let $store;

const startCountdown = function () {
    clearTimeout(timer);

    expires = Date.now() + 30 * 60 * 1000;
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

    if (countdown <= 5 * 60) {
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
        scope: null,
        limited: false,
        totpEnabled: false,
        passkey: false,
        countdown: null,
    },

    getters: {
        isGranted: (state) => (scope) => {
            const all = Object.values(scopes);

            return state.scope && all.indexOf(state.scope) <= all.indexOf(scope);
        },
    },

    mutations: {
        setUser(state, data) {
            state.username = data?.username || null;
            state.scope = data?.scope || null;
            state.limited = data?.limited || false;
            state.totpEnabled = data?.totp_enabled || data?.passkey || false;
            state.passkey = data?.passkey || false;
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

            return axios
                .get('api/session')
                .then((response) => {
                    if (response.data.username) {
                        store.commit('setUser', response.data);
                        startCountdown();
                    } else {
                        store.commit('setUser', null);
                        stopCountdown();
                    }

                    return response.status;
                })
                .catch((error) => {
                    store.commit('setUser', null);
                    stopCountdown();

                    if (error.response?.status === 403) {
                        store.commit('setLocked', null, { root: true });
                    }

                    return error.response?.status;
                });
        },

        async login(store, data) {
            $store = store;

            return await request.post('api/session', data, null, {
                201: (response) => {
                    store.commit('setUser', response.data);
                    startCountdown();

                    return response;
                },
                401: (response) => {
                    return response;
                },
                403: (response) => {
                    store.commit('setLocked', null, { root: true });

                    return response;
                },
                422: (response) => {
                    return response;
                },
            });
        },

        logout({ commit }) {
            return axios
                .delete('api/session')
                .then(
                    () => true,
                    (response) => response.status === 401,
                )
                .then((result) => {
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
