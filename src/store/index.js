/* eslint-disable no-param-reassign */

import Vue from 'vue';
import Vuex from 'vuex';

import views from '../router/views';

import auth from './auth';
import algolia from 'contao-package-list/src/store/algolia';
import cloud from './cloud';
import config from './config';
import modals from 'contao-package-list/src/store/modals';
import packages from './packages';
import contao from './contao';
import server from './server';
import tasks from './tasks';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: { auth, algolia, cloud, config, modals, packages, contao, server, tasks },

    state: {
        view: views.INIT,
        error: null,
        locked: false,
        safeMode: false,
    },

    mutations: {
        setView(state, view) {
            state.view = view;
        },

        setLocked(state) {
            state.view = 'login';
            state.locked = true;
        },

        setError(state, error) {
            if (state.error) {
                return;
            }

            state.error = error;
        },

        setSafeMode(state, value) {
            state.safeMode = !!value;
        },

        apiError: (state, response, request = null) => {
            if (state.error) {
                return;
            }

            state.error = {
                type: 'about:blank',
                status: response.status || '',
                response,
                request,
            };
        },
    },

    actions: {
        reset({ commit }) {
            commit('server/composer/setCache');
            commit('server/config/setCache');
            commit('server/contao/setCache');
            commit('server/opcache/setCache');
            commit('server/phpinfo/setCache');
            commit('server/php-cli/setCache');
            commit('server/php-web/setCache');
            commit('server/self-update/setCache');
            commit('tasks/setInitialized', false);
            commit('cloud/setStatus', null);
        },
    },
});

export default store;
