/* eslint-disable no-param-reassign */

import Vue from 'vue';
import Vuex from 'vuex';

import views from '../router/views';

import auth from './auth';
import algolia from 'contao-package-list/src/store/algolia';
import config from './config';
import modals from 'contao-package-list/src/store/modals';
import packages from './packages';
import contao from './contao';
import server from './server';
import tasks from './tasks';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: { auth, algolia, config, modals, packages, contao, server, tasks },

    state: {
        view: views.INIT,
        error: null,
        safeMode: false,
        contaoVersion: null,
        contaoApi: null,
    },

    mutations: {
        setView(state, view) {
            state.view = view;
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

        setVersions(state, result) {
            state.contaoVersion = result.version;
            state.contaoApi = result.api;
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
});

export default store;
