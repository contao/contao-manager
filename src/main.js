import Vue from 'vue';
import VueResource from 'vue-resource';
import Cookies from 'js-cookie';

import router from './router';
import store from './store';

import App from './components/App';

Vue.use(VueResource);

Vue.http.options.emulateHTTP = true;

Vue.http.interceptors.push((request, next) => {
    console.log(request);

    request.headers.set('X-XSRF-Token', Cookies.get('contao_manager_xsrf'));

    next((response) => {
        console.log(response);

        if (response.status === 401 && request.url !== 'api/status') {
            store.dispatch('fetchStatus', true);
        } else if (response.headers.get('Content-Type') === 'application/problem+json') {
            store.commit('setError', response.data);
        }
    });
});

/* eslint-disable no-new */
new Vue({
    router,
    store,
    el: '#app',
    render: h => h(App),
});
