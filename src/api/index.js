import Vue from 'vue';

export default {
    fetchStatus() {
        return Vue.http.get('api/status').then(
            response => response.body,
            response => response.body,
        );
    },

    login(username, password) {
        return Vue.http.post('api/session', { username, password }).then(
            response => ({
                success: true,
                username: response.username,
            }),
            () => ({
                success: false,
            }),
        );
    },

    logout() {
        return Vue.http.delete('api/session').then(
            () => true,
            () => false,
        );
    },

    configure(config) {
        return Vue.http.patch('api/config/manager', config);
    },

    setGithubToken(token) {
        return Vue.http.put('api/config/auth/github-oauth', { token });
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

    runNextTask(timeout = 1000) {
        return new Promise((resolve) => {
            let request;

            Vue.http.get('api/tasks/run', {
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
