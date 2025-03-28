import { createStore } from 'vuex';

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

const store = createStore({
    modules: { auth, algolia, cloud, config, modals, packages, contao, server, tasks },

    state: {
        view: views.INIT,
        setupStep: 0,
        migrationsType: '',
        error: null,
        locked: false,
        safeMode: false,
    },

    mutations: {
        setView(state, view) {
            state.view = view;

            if (view === views.READY) {
                state.setupStep = 0;
            }
        },

        setLocked(state) {
            state.view = views.LOGIN;
            state.locked = true;
        },

        setError(state, error) {
            if (error && state.error) {
                return;
            }

            state.error = error;
        },

        setSafeMode(state, value) {
            state.safeMode = !!value;
        },

        setup(state, step) {
            state.view = views.SETUP;
            state.setupStep = step;
        },

        checkMigrations(state, type = '') {
            state.view = views.MIGRATION;
            state.migrationsType = type;
        },

        apiError: (state, response) => {
            if (state.error) {
                return;
            }

            if (response.headers['content-type'] === 'application/problem+json') {
                const error = response.data;
                error.response = response;
                state.error = error;
            } else {
                state.error = {
                    type: 'about:blank',
                    status: response.status || '',
                    response,
                };
            }
        },
    },

    actions: {
        reset({ commit }) {
            commit('server/composer/setCache');
            commit('server/config/setCache');
            commit('server/contao/setCache');
            commit('server/database/setCache');
            commit('server/adminUser/setCache');
            commit('server/opcache/setCache');
            commit('server/phpinfo/setCache');
            commit('server/php-cli/setCache');
            commit('server/php-web/setCache');
            commit('server/self-update/setCache');
            commit('contao/install-tool/setCache');
            commit('contao/backup/setCache');
            commit('tasks/setInitialized', false);
            commit('cloud/setStatus', null);
            commit('setSafeMode', false);
        },
    },
});

export default store;
