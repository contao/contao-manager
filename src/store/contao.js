/* eslint-disable no-param-reassign */

import Vue from 'vue';

import accessKey from './contao/accessKey';

export default {
    namespaced: true,

    modules: {
        'access-key': accessKey,
    },

    actions: {
        install({ dispatch }, version) {
            const task = {
                name: 'contao/install',
                config: {
                    version,
                },
            };

            return Vue.http.patch(
                'api/config/composer',
                {
                    'preferred-install': 'dist',
                    'store-auths': false,
                    'optimize-autoloader': true,
                    'sort-packages': true,
                    'discard-changes': true,
                },
            ).then(() => dispatch('tasks/execute', task, { root: true }));
        },
    },
};
