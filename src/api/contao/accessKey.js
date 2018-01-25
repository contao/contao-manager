import Vue from 'vue';

export default {
    get() {
        return Vue.http.get('api/contao/access-key').then(
            response => response.body['access-key'],
        );
    },

    set(user, password) {
        return Vue.http.put('api/contao/access-key', { user, password }).then(
            response => response.body['access-key'],
        );
    },

    remove() {
        return Vue.http.delete('api/contao/access-key').then(
            response => response.body['access-key'],
        );
    },
};
