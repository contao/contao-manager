/* eslint-disable no-param-reassign */

import Vue from 'vue';
import Vuex from 'vuex';

import api from '../api';
import views from '../router/views';

import auth from './auth';
import tasks from './tasks';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: { auth, tasks },

    state: {
        view: views.INIT,
        status: null,
        config: null,
        error: null,
        version: null,
        debugMode: null,
    },

    mutations: {
        setView(state, view) {
            state.view = view;
        },

        setStatus(state, { status, config, version }) {
            state.status = status;
            state.config = config;
            state.version = version;
        },

        setError(state, error) {
            if (state.error === null) {
                state.error = error;
            }
        },

        setDebugMode(state, status) {
            state.debugMode = status;
        },
    },

    actions: {
        apiError: ({ commit }, statusCode) => {
            commit('setError', {
                title: Vue.i18n.translate('ui.app.apiError'),
                type: 'about:blank',
                status: statusCode || '',
            });
        },

        install: ({ dispatch }, version) => {
            const task = {
                type: 'install',
                version,
            };

            return api.config.composer.patch(
                {
                    'preferred-install': 'dist',
                    'store-auths': false,
                    'optimize-autoloader': true,
                    'sort-packages': true,
                    'discard-changes': true,
                },
            ).then(() => dispatch('tasks/execute', task, { root: true }));
        },

        refreshDebugMode({ commit }) {
            api.contao.accessKey.get().then((accessKey) => {
                commit('setDebugMode', accessKey !== '');
            });
        },
    },
});

export default store;
