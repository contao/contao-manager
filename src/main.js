import axios from 'axios'
import Clipboard from 'v-clipboard';
import { createNotivue, push } from 'notivue';

import bootstrap from 'contao-package-list/src/bootstrap';
import router from './router';
import views from './router/views';
import store from './store';
import i18n from './i18n';

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
    response => {
        store.commit('auth/renewCountdown');

        return response;
    },
    function (error) {
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

        if (url.substring(0, 4) === 'api/'
            && response.headers['content-type'] !== 'application/json'
            && response.status >= 400
            && response.status <= 599
        ) {
            store.commit('setError', {
                type: 'about:blank',
                status: response.status,
                response,
            });

            return Promise.reject(error);
        }
    }
);

const notivue = createNotivue({
    position: 'bottom-right',
    limit: 4,
    enqueue: true,
});

bootstrap(App, i18n, [store, router, Clipboard, notivue], (app) => {
    app.config.globalProperties.$notify = push;

    app.config.globalProperties.$request =  new Proxy(axios, {
        get (target, prop) {
            const methods = {
                request: 1,
                get: 2,
                delete: 2,
                head: 2,
                options: 2,
                post: 3,
                put: 3,
                patch: 3,
            };

            if (!Object.keys(methods).includes(prop)) {
                return Reflect.get(...arguments);
            }

            return async (...args) => {
                let response;
                let handler = {};

                if (args.length > methods[prop]) {
                    handler = args.pop();
                }

                try {
                    response = await target[prop](...args);
                } catch (err) {
                    response = err.response;
                }

                if (handler[response.status]) {
                    handler[response.status](response);
                } else if (response.status >= 400 && response.status <= 599) {
                    store.commit('apiError', response);
                }
            }
        }
    });
});
