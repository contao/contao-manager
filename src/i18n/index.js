
import Vue from 'vue';
import vuexI18n from 'vuex-i18n';

import store from '../store';
import locales from './locales';

import fallback from '../assets/i18n/en.json';

Vue.use(vuexI18n.plugin, store);

Vue.i18n.add('en', fallback);
Vue.i18n.set('en');
Vue.i18n.fallback('en');

export default {
    init() {
        let userLang = window.localStorage.getItem('contao_manager_locale');

        if (!userLang) {
            userLang = navigator.language || navigator.userLanguage;
        }

        return this.load(userLang);
    },

    load(locale) {
        window.localStorage.setItem('contao_manager_locale', locale);

        if (Vue.i18n.exists(locale)) {
            Vue.i18n.set(locale);
            return new Promise(resolve => resolve());
        }

        if (!locales[locale]) {
            if (locale.length === 5) {
                return this.load(locale.slice(0, 2));
            }

            return new Promise(resolve => resolve());
        }

        return Vue.http.get(`assets/i18n/${locale}.json`).then(
            response => response.json().then((json) => {
                console.log(json);
                Vue.i18n.add(locale, json);
                Vue.i18n.set(locale);
            }),
            () => {},
        );
    },
};
