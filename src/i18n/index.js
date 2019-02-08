
import Vue from 'vue';
import vuexI18n from 'vuex-i18n';

import store from '../store';

const locales = {
    en: () => import('./en.json'),
    de: () => import('./de.json'),
    br: () => import('./br.json'),
    cs: () => import('./cs.json'),
    es: () => import('./es.json'),
    fa: () => import('./fa.json'),
    fr: () => import('./fr.json'),
    ja: () => import('./ja.json'),
    lv: () => import('./lv.json'),
    nl: () => import('./nl.json'),
    pl: () => import('./pl.json'),
    pt: () => import('./pt.json'),
    ru: () => import('./ru.json'),
    sr: () => import('./sr.json'),
    zh: () => import('./zh.json'),
};

const i18n = {
    init() {
        let userLang = window.localStorage.getItem('contao_manager_locale');

        if (!userLang) {
            userLang = navigator.language || navigator.userLanguage;
        }

        return this.load(userLang);
    },

    async switch(locale) {
        window.localStorage.setItem('contao_manager_locale', locale);
        store.commit('packages/search/reset');

        this.load(locale);
    },

    async load(locale) {
        if (Vue.i18n.localeExists(locale)) {
            Vue.i18n.set(locale);
            return;
        }

        if (!locales[locale]) {
            if (locale.length === 5) {
                return this.load(locale.slice(0, 2));
            }

            throw `Locale ${locale} does not exist.`;
        }

        Vue.i18n.add(locale, Object.assign({}, await locales[locale]()));
        Vue.i18n.set(locale);
    },
};


Vue.use(vuexI18n.plugin, store);
Vue.i18n.fallback('en');
i18n.load('en');

export default i18n;
