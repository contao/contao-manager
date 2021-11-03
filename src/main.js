import Vue from 'vue';
import VueResource from 'vue-resource';
import VueClipboard from 'v-clipboard';

import bootstrap from 'contao-package-list/src/bootstrap';
import router from './router';
import views from './router/views';
import store from './store';
import i18n from './i18n';
import './filters';

import App from './components/App';

Vue.use(VueResource);
Vue.use(VueClipboard);

Vue.http.options.emulateHTTP = true;
Vue.http.headers.common['Accept'] = 'application/json';

Vue.http.interceptors.push((request, next) => {
    const url = request.url;

    if (request.url.slice(0, 4) === 'api/') {
        request.headers.set('Accept-Language', i18n.plugin.locale);
    }

    next((response) => {
        if (response.status === 401 && url !== 'api/session') {
            store.commit('auth/reset');
            store.commit('setView', views.LOGIN);
            return;
        }

        if (response.headers.get('Content-Type') === 'application/problem+json') {
            if (response.status === 500) {
                store.commit('setError', response.data);
            }

            throw response;
        }

        if (url === 'api/session' && response.status !== 200) {
            return;
        }

        if (request.url.substring(0, 4) === 'api/'
            && response.headers.get('Content-Type') !== 'application/json'
            && response.status >= 400
            && response.status <= 599
        ) {
            store.commit('setError', {
                type: 'about:blank',
                status: response.status,
                request,
                response,
            });

            throw response;
        }

        store.commit('auth/renewCountdown');
    });
});

bootstrap(Vue, App, router, store, i18n);
