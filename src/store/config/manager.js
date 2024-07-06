/* eslint-disable no-param-reassign */

import Vue from 'vue';

export default {
    namespaced: true,

    actions: {
        get () {
            return Vue.http.get('api/config/manager').then(response => response.body);
        },
    },
};
