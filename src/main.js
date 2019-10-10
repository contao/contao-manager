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
        request.headers.set('Accept-Language', Vue.i18n.locale());
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

        if (url === 'api/session' && response.status !== 200) {
            return;
        }

        store.commit('auth/renewCountdown');
    });
});

i18n.init().then(() => {
    /* eslint-disable no-new */
    const $vue = new Vue({
        router,
        store,
        render: h => h(App),
    });

    $vue.$store.commit('packages/details/setRouter', router);

    $vue.$mount('#app');
});
