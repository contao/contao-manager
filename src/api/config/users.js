import Vue from 'vue';

export default {
    getTokens(username) {
        return Vue.http.get(`api/users/${username}/tokens`).then(
            response => response.body,
        );
    },

    createToken(username, payload) {
        return Vue.http.post(`api/users/${username}/tokens`, payload).then(
            response => response.body,
        );
    },
};
