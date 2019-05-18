import Vue from 'vue';
import VueResource from 'vue-resource';

import router from './router';
import views from './router/views';
import store from './store';
import i18n from './i18n';
import './filters';

import App from './components/App';

Vue.use(VueResource);

Vue.http.options.emulateHTTP = true;

Vue.http.interceptors.push((request, next) => {
    const url = request.url;

    if (request.url.slice(0, 4) === 'api/') {
        request.url = `${request.url}?_locale=${Vue.i18n.locale()}`;
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

            throw response.data;
        }

        // Successful request, Renew login expiration 30 minutes
        if (url !== 'api/session' || response.status !== 204) {
            store.commit('auth/renewCountdown');
        }
    });
});

i18n.init().then(() => {
    /* eslint-disable no-new */
    new Vue({
        router,
        store,
        render: h => h(App),
    }).$mount('#app');
});
