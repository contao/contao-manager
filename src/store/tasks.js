/* eslint-disable no-param-reassign */

import api from '../api';

const pollTask = ({ commit }, taskId, resolve, reject) => {
    commit('setTaskId', taskId);
    commit('setStatus', 'running');
    commit('setProgress', null);

    const interval = setInterval(
        () => (api.getTask(taskId).then(
            ({ status, type, output }) => {
                commit('setProgress', { type, output });

                switch (status) {
                    case 'PENDING':
                    case 'RUNNING':
                        commit('setStatus', 'running');
                        break;

                    case 'FINISHED':
                        commit('setStatus', 'success');
                        clearInterval(interval);
                        resolve();
                        break;

                    case 'ERROR':
                    default:
                        commit('setStatus', 'error');
                        clearInterval(interval);
                        reject();
                }
            },
        )),
        1000,
    );
};

export default {
    namespaced: true,

    state: {
        currentId: null,
        status: null,
        type: null,
        consoleOutput: '',
    },

    mutations: {
        setTaskId(state, taskId) {
            state.currentId = taskId;
        },
        setStatus(state, status) {
            state.status = status;
        },
        setProgress(state, progress) {
            if (progress === null) {
                state.type = null;
                state.consoleOutput = '';
            } else {
                state.type = progress.type;
                state.consoleOutput = progress.output;
            }
        },
    },

    actions: {
        reload(store) {
            return new Promise((resolve, reject) => {
                if (store.state.currentId !== null) {
                    reject();
                }

                api.getTasks().then(
                    (tasks) => {
                        const keys = Object.keys(tasks);

                        if (keys.length) {
                            for (let i = 0; i < keys.length; i += 1) {
                                const task = tasks[keys[i]];

                                if (task.status === 'PENDING') {
                                    api.deleteTask(task.id);
                                } else {
                                    pollTask(store, task.id, resolve, reject);
                                    return;
                                }
                            }
                        }

                        resolve();
                    },
                );
            });
        },

        run(store, taskId) {
            return new Promise((resolve, reject) => {
                if (store.state.currentId !== null) {
                    reject();
                }

                api.runNextTask();

                pollTask(store, taskId, resolve, reject);
            });
        },

        execute({ dispatch }, task) {
            return api.addTask(task).then(
                taskId => dispatch('run', taskId),
            );
        },

        deleteCurrent(store) {
            return api.deleteTask(store.state.currentId).then(
                () => {
                    store.commit('setTaskId', null);
                },
            );
        },
    },
};
