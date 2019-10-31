import Vue from 'vue';
import vuexI18n from 'vuex-i18n';

import store from '../store';

const merge = async (...files) => {
    let data = {}, i;

    for(i = 0; i < files.length; i++) {
        data = Object.assign(data, (await files[i]).default);
    }

    return data;
};

const setLocale = (locale) => {
    Vue.i18n.set(locale);
    store.commit('algolia/setLanguage', locale);
    document.querySelector('html').setAttribute('lang', locale);
};

const locales = {
    en: () => merge(import('contao-package-list/src/i18n/en.json'), import('./en.json')),
    de: () => merge(import('contao-package-list/src/i18n/de.json'), import('./de.json')),
    br: () => merge(import('contao-package-list/src/i18n/br.json'), import('./br.json')),
    cs: () => merge(import('contao-package-list/src/i18n/cs.json'), import('./cs.json')),
    es: () => merge(import('contao-package-list/src/i18n/es.json'), import('./es.json')),
    fa: () => merge(import('contao-package-list/src/i18n/fa.json'), import('./fa.json')),
    fr: () => merge(import('contao-package-list/src/i18n/fr.json'), import('./fr.json')),
    it: () => merge(import('contao-package-list/src/i18n/it.json'), import('./it.json')),
    ja: () => merge(import('contao-package-list/src/i18n/ja.json'), import('./ja.json')),
    lv: () => merge(import('contao-package-list/src/i18n/lv.json'), import('./lv.json')),
    nl: () => merge(import('contao-package-list/src/i18n/nl.json'), import('./nl.json')),
    pl: () => merge(import('contao-package-list/src/i18n/pl.json'), import('./pl.json')),
    pt: () => merge(import('contao-package-list/src/i18n/pt.json'), import('./pt.json')),
    ru: () => merge(import('contao-package-list/src/i18n/ru.json'), import('./ru.json')),
    sr: () => merge(import('contao-package-list/src/i18n/sr.json'), import('./sr.json')),
    zh: () => merge(import('contao-package-list/src/i18n/zh.json'), import('./zh.json')),
};

const i18n = {
    async init() {
        Vue.i18n.fallback('en');
        await this.load('en');

        const userLang = localStorage.getItem('contao_manager_locale');

        if (userLang && locales[userLang]) {
            return this.load(userLang);
        }

        const languages = Array.from(navigator.languages);

        for (let i = 0; i < languages.length; i += 1) {
            if (locales[languages[i]]) {
                return this.load(languages[i]);
            }
        }

        return null;
    },

    async switch(locale) {
        window.localStorage.setItem('contao_manager_locale', locale);

        this.load(locale);

        store.dispatch('algolia/discover');
    },

    async load(locale) {
        if (Vue.i18n.localeExists(locale)) {
            setLocale(locale);
            return;
        }

        if (!locales[locale]) {
            if (locale.length === 5) {
                return this.load(locale.slice(0, 2));
            }

            throw `Locale ${locale} does not exist.`;
        }

        Vue.i18n.add(locale, Object.assign({}, await locales[locale]()));
        setLocale(locale);
    },
};

Vue.use(vuexI18n.plugin, store);

export default i18n;
