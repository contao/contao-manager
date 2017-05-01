
import Vue from 'vue';
import vuexI18n from 'vuex-i18n';

import store from '../store';

Vue.use(vuexI18n.plugin, store);

Vue.i18n.set('en');

export default {
    detect() {
        const userLang = navigator.language || navigator.userLanguage;

        return this.load(userLang);
    },

    load(locale) {
        if (Vue.i18n.exists(locale)) {
            Vue.i18n.set(locale);
            return new Promise(resolve => resolve());
        }

        return Vue.http.get(`api/i18n/${locale}`).then(
            (response) => {
                Vue.i18n.add(locale, response.body);
                Vue.i18n.set(locale);
            },
        );
    },
};
