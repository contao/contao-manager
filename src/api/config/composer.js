import Vue from 'vue';

export default {
    get() {
        return Vue.http.get('api/config/composer').then(
            response => response.body,
        );
    },

    put(config) {
        return Vue.http.put('api/config/composer', config).then(
            response => response.body,
        );
    },

    patch(config) {
        return Vue.http.patch('api/config/composer', config).then(
            response => response.body,
        );
    },
};
