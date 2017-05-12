/* eslint-disable no-param-reassign */

import api from '../api';

const pollTask = ({ commit }, resolve, reject) => {
    let pending = 0;

    const fetch = () => {
        api.getTask().then(
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

                        api.runTask(pending * 500).then(() => {
                            setTimeout(fetch, pending * 1000);
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
            () => {
                commit('setStatus', null);
                commit('setProgress', null);
            },
        );
    };

    fetch();
};

export default {
    namespaced: true,

    state: {
        status: null,
        type: null,
        consoleOutput: '',
    },

    mutations: {
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
                if (store.state.status !== null) {
                    reject();
                }

                api.getTask().then(
                    (task) => {
                        if (task.status === 'PENDING') {
                            api.deleteTask();
                        } else {
                            store.commit('setStatus', 'running');
                            store.commit('setProgress', null);

                            pollTask(store, resolve, reject);
                            return;
                        }

                        resolve();
                    },
                );
            });
        },

        run(store) {
            return new Promise((resolve, reject) => {
                if (store.state.status !== null) {
                    reject();
                }

                store.commit('setStatus', 'running');
                store.commit('setProgress', null);

                pollTask(store, resolve, reject);
            });
        },

        execute(store, task) {
            return new Promise((resolve, reject) => {
                if (store.state.status !== null) {
                    reject();
                }

                store.commit('setStatus', 'running');
                store.commit('setProgress', null);

                api.addTask(task).then(() => {
                    pollTask(store, resolve, reject);
                });
            });
        },

        stop(store) {
            return new Promise((resolve, reject) => {
                if (store.state.status === null) {
                    reject();
                }

                api.stopTask().then(
                    () => {
                        resolve();
                    },
                    () => {
                        reject();
                    },
                );
            });
        },

        deleteCurrent(store) {
            const deleteTask = () => store.commit('setStatus', null);
            return api.deleteTask().then(deleteTask, deleteTask);
        },
    },
};
