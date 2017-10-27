import Vue from 'vue';

import config from './config/index';
import files from './files';
import session from './session';

export default {
    config,
    files,
    session,

    fetchStatus() {
        return Vue.http.get('api/status').then(
            response => response.body,
            response => response.body,
        );
    },

    configure(config) {
        return Vue.http.patch('api/config/manager', config);
    },

    setGithubToken(token) {
        return Vue.http.put('api/config/auth/github-oauth', { token });
    },

    runTask(timeout = 1000) {
        return new Promise((resolve) => {
            let request;

            Vue.http.put('api/task/status', { status: 'started' }, {
                before: (r) => {
                    request = r;
                },
            });

            setTimeout(() => {
                request.abort();
                resolve();
            }, timeout);
        });
    },

    stopTask() {
        return Vue.http.put('api/task/status', { status: 'terminated' }).then(
            response => response.body,
        );
    },

    getTask() {
        return Vue.http.get('api/task').then(
            response => response.body,
        );
    },

    addTask(task) {
        return new Promise((resolve) => {
            let request;

            Vue.http.put('api/task', task, {
                before: (r) => {
                    request = r;
                },
            });

            setTimeout(() => {
                request.abort();
                resolve();
            }, 1000);
        });
    },

    deleteTask() {
        return Vue.http.delete('api/task');
    },

    getPackages() {
        return Vue.http.get('api/packages').then(
            response => (response.body),
        );
    },

    validateConstraint(constraint) {
        return Vue.http.post('api/constraint', { constraint }).then(
            response => response.body.status === 'OK',
        );
    },
};
