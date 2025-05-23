import axios from 'axios';
import { createNotivue, push } from 'notivue';

import bootstrap from 'contao-package-list/src/bootstrap';
import router from './router';
import views from './router/views';
import store from './store';
import i18n from './i18n';
import request from './tools/request';

import App from './components/App';

axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use(function (config) {
    if (config.url.slice(0, 4) === 'api/') {
        config.headers['Accept-Language'] = i18n.plugin.global.locale;

        if (['PUT', 'PATCH', 'DELETE'].includes(config.method)) {
            config.headers['X-HTTP-Method-Override'] = config.method;
            config.method = 'POST';
        }
    }

    return config;
});

axios.interceptors.response.use(
    (response) => {
        store.commit('auth/renewCountdown');

        return response;
    },
    function (error) {
        if (!error.response) {
            return Promise.reject(error);
        }

        const response = error.response;
        const url = response.config.url;

        if (response.status === 401 && url !== 'api/session') {
            store.commit('auth/reset');
            store.commit('setView', views.LOGIN);
            return response;
        }

        if (response.headers['content-type'] === 'application/problem+json') {
            if (response.status === 500) {
                store.commit('setError', response.data);
            }

            return Promise.reject(error);
        }

        if (url === 'api/session' && response.status !== 200) {
            return response;
        }

        if (url.substring(0, 4) === 'api/' && response.headers['content-type'] !== 'application/json' && response.status >= 400 && response.status <= 599) {
            store.commit('setError', {
                type: 'about:blank',
                status: response.status,
                response,
            });

            return Promise.reject(error);
        }

        return Promise.reject(error);
    },
);

const notivue = createNotivue({
    position: 'bottom-right',
    limit: 4,
    enqueue: true,
});

bootstrap(App, i18n, [store, router, notivue], (app) => {
    app.config.globalProperties.$notify = push;
    app.config.globalProperties.$request = request;
});
