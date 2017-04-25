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
        selftest: null,
        autoconfig: null,
        error: null,
    },

    mutations: {
        setStatus(state, { status, selftest, autoconfig }) {
            state.status = status;
            state.selftest = selftest;
            state.autoconfig = autoconfig;
        },

        setError(state, error) {
            state.error = error;
        },
    },

    actions: {
        fetchStatus: ({ state, commit, dispatch }, force) => {
            if (!force && state.status !== null) {
                return state.status;
            }

            return api.fetchStatus().then(
                (result) => {
                    commit('setStatus', result);

                    if (result.username) {
                        commit('auth/setLogin', result.username);
                    } else {
                        commit('auth/setLogout');
                    }

                    if (result.status === apiStatus.OK || result.status === apiStatus.EMPTY) {
                        dispatch('tasks/reload').catch(() => {});
                    }

                    return result.status;
                },
            );
        },

        configure: ({ state, dispatch }, config) => (
            api.configure(config || state.autoconfig)
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
