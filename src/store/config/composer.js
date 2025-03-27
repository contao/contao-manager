/* eslint-disable no-param-reassign */

import axios from 'axios';

export default {
    namespaced: true,

    actions: {
        get() {
            return axios.get('api/config/composer').then((response) => response.data);
        },

        put(store, data) {
            return axios.put('api/config/composer', data).then((response) => response.data);
        },

        patch(store, data) {
            return axios.patch('api/config/composer', data).then((response) => response.data);
        },

        writeDefaults({ dispatch }) {
            return dispatch('patch', {
                config: {
                    'preferred-install': 'dist',
                    'store-auths': false,
                    'optimize-autoloader': true,
                    'sort-packages': true,
                    'discard-changes': true,
                },
            }).then((response) => response.data);
        },
    },
};
