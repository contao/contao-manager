import Vue from 'vue';

import config from './config/index';
import files from './files';
import system from './system';
import session from './session';

export default {
    config,
    files,
    system,
    session,

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
