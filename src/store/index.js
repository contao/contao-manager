/* eslint-disable no-param-reassign */

import Vue from 'vue';
import Vuex from 'vuex';

import api from '../api';
import apiStatus from '../api/status';

import auth from './auth';
import tasks from './tasks';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: { auth, tasks },

    state: {
        status: null,
    },

    mutations: {
        setStatus(state, status) {
            state.status = status;
        },
    },

    actions: {
        fetchStatus: ({ state, rootState, commit, dispatch }, force) => {
            if (!force && state.status !== null) {
                return state.status;
            }

            return api.fetchStatus().then(
                (status) => {
                    if (status !== apiStatus.NEW && !rootState.auth.isLoggedIn) {
                        status = apiStatus.AUTHENTICATE;
                    }

                    commit('setStatus', status);

                    if (status !== apiStatus.NEW && status !== apiStatus.AUTHENTICATE) {
                        dispatch('tasks/reload').catch(() => {});
                    }

                    return status;
                },
            );
        },

        configure: ({ dispatch }, { username, password, config }) => (
            api.configure(username, password, config).then(
                () => (dispatch('auth/login', { username, password })),
            )
        ),

        install: ({ dispatch }, version) => (
            api.install(version).then(
                taskId => dispatch('tasks/run', taskId, { root: true }),
            ).then(
                () => dispatch('fetchStatus', true, { root: true }),
            )
        ),
    },
});

export default store;
