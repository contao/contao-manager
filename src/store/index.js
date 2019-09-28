/* eslint-disable no-param-reassign */

import Vue from 'vue';
import Vuex from 'vuex';

import views from '../router/views';

import auth from './auth';
import algolia from 'contao-package-list/src/store/algolia';
import config from './config';
import packages from './packages';
import contao from './contao';
import server from './server';
import tasks from './tasks';

Vue.use(Vuex);

const store = new Vuex.Store({
    modules: { auth, algolia, config, packages, contao, server, tasks },

    state: {
        view: views.INIT,
        error: null,
        safeMode: false,
        contaoVersion: null,
        apiVersion: null,
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
            state.apiVersion = result.api;
        },

        apiError: (state, { status, body }) => {
            if (state.error) {
                return;
            }

            state.error = {
                title: Vue.i18n.translate('ui.app.apiError'),
                type: 'about:blank',
                status: status || '',
                detail: body || '',
            };
        },
    },
});

export default store;
