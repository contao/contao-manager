import Vue from 'vue';

export default {
    get() {
        return Vue.http.get('api/session').then(
            response => ({
                statusCode: response.status,
                username: (response.body && response.body.username) ? response.body.username : null,
            }),
            response => ({
                statusCode: response.status,
                username: null,
            }),
        );
    },

    create(username, password) {
        return Vue.http.post('api/session', { username, password }).then(
            response => ({
                success: true,
                username: response.username,
            }),
            () => ({
                success: false,
                username: null,
            }),
        );
    },

    delete() {
        return Vue.http.delete('api/session').then(
            () => true,
            () => false,
        );
    },
};
