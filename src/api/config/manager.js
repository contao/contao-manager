import Vue from 'vue';

export default {
    get() {
        return Vue.http.get('api/config/manager').then(
            response => response.body,
        );
    },

    put(config) {
        return Vue.http.put('api/config/manager', config).then(
            response => response.body,
        );
    },

    patch(config) {
        return Vue.http.patch('api/config/manager', config).then(
            response => response.body,
        );
    },
};
