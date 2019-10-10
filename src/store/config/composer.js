/* eslint-disable no-param-reassign */

import Vue from 'vue';

export default {
    namespaced: true,

    actions: {
        get() {
            return Vue.http.get('api/config/composer').then(response => response.body);
        },

        put(store, data) {
            return Vue.http.put('api/config/composer', data).then(response => response.body);
        },

        patch(store, data) {
            return Vue.http.patch('api/config/composer', data).then(response => response.body);
        },

        writeDefaults({ dispatch }) {
            return dispatch('patch', {
                'preferred-install': 'dist',
                'store-auths': false,
                'optimize-autoloader': true,
                'sort-packages': true,
                'discard-changes': true,
            }).then(response => response.body);
        },
    },
};
