import Vue from 'vue';

export default {
    get() {
        return Vue.http.get('api/config/auth').then(
            response => response.body,
        );
    },

    put(config) {
        return Vue.http.put('api/config/auth', config).then(
            response => response.body,
        );
    },

    patch(config) {
        return Vue.http.patch('api/config/auth', config).then(
            response => response.body,
        );
    },

    setGithubToken(token) {
        return Vue.http.put('api/config/auth/github-oauth', { token });
    },
};
