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
        config: null,
        error: null,
        installComplete: false,
    },

    mutations: {
        setStatus(state, { status, config }) {
            state.status = status;
            state.config = config;
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
                        dispatch('tasks/reload').then(
                            (task) => {
                                if (task.type === 'install') {
                                    state.installComplete = true;
                                }
                            },
                            () => {},
                        );
                    }

                    return result.status;
                },
            );
        },

        configure: ({ state }, props) => {
            const config = props || state.config;

            if (config.github_oauth_token) {
                api.setGithubToken(config.github_oauth_token);
                delete config.github_oauth_token;
            }

            api.configure(config);
        },

        install: ({ state, dispatch }, version) => (
            api.install(version).then(
                taskId => dispatch('tasks/run', taskId, { root: true }),
            ).then(
                () => { state.installComplete = true; },
            )
        ),
    },
});

export default store;
