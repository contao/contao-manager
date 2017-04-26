/* eslint-disable no-param-reassign */

import api from '../api';

const pollTask = ({ commit }, taskId, resolve, reject) => {
    let pending = 0;

    const fetch = () => (api.getTask(taskId).then(
        (task) => {
            commit('setProgress', task);

            switch (task.status) {
                case 'PENDING':
                    pending += 1;

                    if (pending > 5) {
                        commit('setStatus', 'failed');
                        reject(task);
                        return;
                    }

                    api.runNextTask().then(() => {
                        setTimeout(fetch, 2000);
                    });
                    break;

                case 'RUNNING':
                    commit('setStatus', 'running');
                    setTimeout(fetch, 1000);
                    break;

                case 'FINISHED':
                    commit('setStatus', 'success');
                    resolve(task);
                    break;

                case 'ERROR':
                    commit('setStatus', 'error');
                    reject(task);
                    break;

                default:
                    commit('setStatus', 'failed');
                    reject(task);
            }
        },
    ));

    fetch();
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
                                    store.commit('setTaskId', task.id);
                                    store.commit('setStatus', 'running');
                                    store.commit('setProgress', null);

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

                store.commit('setTaskId', taskId);
                store.commit('setStatus', 'running');
                store.commit('setProgress', null);

                pollTask(store, taskId, resolve, reject);
            });
        },

        execute({ dispatch }, task) {
            return api.addTask(task).then(
                taskId => dispatch('run', taskId),
            );
        },

        deleteCurrent(store) {
            const deleteTask = () => store.commit('setTaskId', null);
            return api.deleteTask(store.state.currentId).then(deleteTask, deleteTask);
        },
    },
};
