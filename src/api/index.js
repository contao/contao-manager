import Vue from 'vue';

import apiStatus from './status';

export default {
    fetchStatus() {
        const handleStatus = (response) => {
            if (response.status === 401) {
                return { status: apiStatus.AUTHENTICATE };
            }

            if (response.status === 500 && response.body.status === apiStatus.FAIL) {
                return {
                    status: apiStatus.FAIL,
                    selftest: response.body.selftest || null,
                };
            }

            if (!response.ok) {
                return {
                    status: apiStatus.FAIL,
                    error: response.body.status === 'ERROR' ? response.body : response.statusText,
                };
            }

            if (response.status === 200) {
                return response.body;
            }

            return { status: apiStatus.FAIL, error: response.statusText };
        };

        return Vue.http.get('api/status').then(handleStatus, handleStatus);
    },

    login(username, password) {
        return Vue.http.post('api/auth', { username, password })
            .then(
                response => ({
                    token: response.body.token,
                    error: null,
                }),
                response => ({
                    token: null,
                    error: response.body.message,
                }),
            );
    },

    configure(username, password, config) {
        const data = {
            configuration: config,
            credentials: { username, password },
        };

        return Vue.http.post('api/install/configure', data);
    },

    install(version) {
        const data = {
            project: {
                name: 'contao/managed-edition',
            },
        };

        if (version !== '') {
            data.project.version = version;
        }

        return Vue.http.post('api/install/create-project', data).then(
            response => response.body.task,
        );
    },

    runNextTask() {
        return Vue.http.get('api/tasks/run').then(
            response => response.body.task.id,
        );
    },

    getTasks() {
        return Vue.http.get('api/tasks').then(
            response => response.body.tasks,
        );
    },

    getTask(taskId) {
        return Vue.http.get(`api/tasks/${taskId}`).then(
            response => ({
                status: response.body.task.status,
                type: response.body.task.type,
                output: response.body.task.output,
            }),
        );
    },

    addTask(task) {
        return Vue.http.post('api/tasks', task).then(
            response => response.body.task.id,
        );
    },

    deleteTask(taskId) {
        return Vue.http.delete(`api/tasks/${taskId}`);
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
