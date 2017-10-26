import Vue from 'vue';
import VueResource from 'vue-resource';
import Cookies from 'js-cookie';

import router from './router';
import views from './router/views';
import store from './store';
import i18n from './i18n';

import App from './components/App';

Vue.use(VueResource);

Vue.http.options.emulateHTTP = true;

Vue.http.interceptors.push((request, next) => {
    const url = request.url;

    if (request.url.slice(0, 4) === 'api/') {
        request.headers.set('XSRF-TOKEN', Cookies.get('contao_manager_xsrf'));
        request.url = `${request.url}?_locale=${Vue.i18n.locale()}`;
    }

    next((response) => {
        if (response.status === 403 || (response.status === 401 && url !== 'api/session')) {
            store.commit('setView', views.LOGIN);
        } else if (response.headers.get('Content-Type') === 'application/problem+json') {
            if (response.status === 500) {
                store.commit('setError', response.data);
            }

            throw response.data;
        }
    });
});

i18n.init().then(() => {
    /* eslint-disable no-new */
    new Vue({
        router,
        store,
        el: '#app',
        render: h => h(App),
    });
});
