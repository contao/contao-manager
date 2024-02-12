// ***********************************************************
// This example support/component.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

import { mount } from 'cypress/vue2'

import Vue from 'vue';
import VueResource from 'vue-resource';
import store from '../../src/store';
import i18n from '../../src/i18n';

Cypress.Commands.add('mount', (component, options = {}) => {
    i18n.init();

    Vue.use(VueResource);

    Vue.http.options.emulateHTTP = true;
    Vue.http.headers.common['Accept'] = 'application/json';
    Vue.http.options.root = 'http://localhost:8000/'

    return mount(component, {
        store,
        i18n: i18n.plugin,
    })
});
