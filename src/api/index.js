import Vue from 'vue';

import apiStatus from './status';

export default {
    fetchStatus() {
        const handleStatus = (response) => {
            if (response.status === 401) {
                return { status: apiStatus.AUTHENTICATE };
            }

            if (response.status === 500 && response.body.status === apiStatus.CONFLICT) {
                return {
                    status: apiStatus.CONFLICT,
                    selftest: response.body.selftest || null,
                };
            }

            if (!response.ok) {
                return {
                    status: apiStatus.CONFLICT,
                    error: response.body.status === 'ERROR' ? response.body : response.statusText,
                };
            }

            if (response.status === 200) {
                return response.body;
            }

            return { status: apiStatus.CONFLICT, error: response.statusText };
        };

        return Vue.http.get('api/status').then(handleStatus, handleStatus);
    },

    login(username, password) {
        return Vue.http.post('api/v1/auth', { username, password })
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

        const submit = payload => (
            Vue.http.post('api/v1/install/configure', payload)
        );

        if (!config) {
            return Vue.http.get('api/v1/install/autoconfig')
                .then((response) => {
                    data.configuration = response.body;
                    data.configuration.php_can_fork = false;
                    data.configuration.php_force_background = true;

                    return submit(data);
                });
        }

        return submit(data);
    },

    install(version) {
        const data = {
            project: {
                name: 'contao/managed-edition',
            },
        };

        if (version !== '') {
            data.version = version;
        }

        return Vue.http.post('api/v1/install/create-project', data).then(
            response => response.body.task,
        );
    },

    runNextTask() {
        return Vue.http.get('api/v1/tasks/run').then(
            response => response.body.task.id,
        );
    },

    getTasks() {
        return Vue.http.get('api/v1/tasks').then(
            response => response.body.tasks,
        );
    },

    getTask(taskId) {
        return Vue.http.get(`api/v1/tasks/${taskId}`).then(
            response => ({
                status: response.body.task.status,
                type: response.body.task.type,
                output: response.body.task.output,
            }),
        );
    },

    addTask(task) {
        return Vue.http.post('api/v1/tasks', task).then(
            response => response.body.task.id,
        );
    },

    deleteTask(taskId) {
        return Vue.http.delete(`api/v1/tasks/${taskId}`);
    },

    getPackages() {
        return Vue.http.get('api/v1/packages').then(
            response => (response.body),
        );
    },

    validateConstraint(constraint) {
        return Vue.http.post('api/v1/constraint', { constraint }).then(
            response => response.body.status === 'OK',
        );
    },
};
