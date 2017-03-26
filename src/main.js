import Vue from 'vue';
import VueResource from 'vue-resource';

import router from './router';
import routes from './router/routes';
import store from './store';

import App from './components/App';

Vue.use(VueResource);

Vue.http.options.emulateHTTP = true;

Vue.http.interceptors.push((request, next) => {
    next((response) => {
        if (response.status === 500 && response.body.status === 'ERROR') {
            store.commit('setError', response.body);
        } else if (response.status === 401) {
            router.push(routes.login);
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
