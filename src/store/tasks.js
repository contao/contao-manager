/* eslint-disable no-param-reassign */

import Vue from 'vue';

const statusMap = {
    started: 'running',
    terminated: 'success',
    error: 'error',
};

const pollTask = ({ commit }, resolve, reject) => {
    let pending = 0;

    const fetch = () => Vue.http.get('api/task').then(
        (response) => {
            const task = response.body;

            commit('setCurrent', task);

            switch (task.status) {
                case 'ready':
                    pending += 1;

                    if (pending > 5) {
                        commit('setStatus', 'failed');
                        reject(task);
                        return;
                    }

                    Vue.http.patch('api/task', { status: 'started' }, {
                        before: (request) => {
                            setTimeout(() => {
                                request.abort();
                            }, pending * 500);
                        },
                    }).then(() => {
                        setTimeout(fetch, pending * 1000);
                    });
                    break;

                default:
                case 'active':
                    commit('setStatus', 'running');
                    setTimeout(fetch, 1000);
                    break;

                case 'terminated': // BC
                case 'complete':
                    commit('setStatus', 'success');
                    resolve(task);
                    break;

                case 'stopped':
                case 'error':
                    commit('setStatus', 'error');
                    reject(task);
                    break;
            }
        },
        () => {
            commit('setStatus', null);
            commit('setCurrent', null);
            reject();
        },
    );

    setTimeout(fetch, 1000);
};

const failTask = (response, store, resolve, reject) => {
    // Request was cancelled, which is normal when starting a task
    if (response.status === 0) {
        pollTask(store, resolve, reject);
        return;
    }

    store.commit('setStatus', 'failed');
    store.commit('setCurrent', null);
    reject();
};

export default {
    namespaced: true,

    state: {
        status: null,
        type: null,
        consoleOutput: '',
        current: null,
    },

    mutations: {
        setStatus(state, status) {
            state.status = status;
        },
        setCurrent(state, task) {
            state.current = task;
        },
    },

    actions: {
        reload(store) {
            return new Promise((resolve, reject) => {
                if (store.state.status !== null) {
                    reject();
                }

                return Vue.http.get('api/task').then(
                    response => response.body,
                ).then(
                    (task) => {
                        if (task) {
                            if (task.status === 'ready') {
                                Vue.http.delete('api/task');
                            } else if (task.status === 'finished') {
                                store.commit('setStatus', statusMap[task.status]);
                                store.commit('setCurrent', task);
                            } else {
                                store.commit('setStatus', statusMap[task.status] !== undefined ? statusMap[task.status] : 'ready');
                                store.commit('setCurrent', task);

                                pollTask(store, resolve, reject);
                                return;
                            }
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

                store.commit('setStatus', 'ready');
                store.commit('setCurrent', null);

                pollTask(store, resolve, reject);
            });
        },

        execute(store, task) {
            return new Promise((resolve, reject) => {
                if (store.state.status !== null) {
                    reject();
                }

                store.commit('setStatus', 'ready');
                store.commit('setCurrent', task);

                Vue.http.put('api/task', task, {
                    before: (request) => {
                        setTimeout(() => {
                            request.abort();
                        }, 1000);
                    },
                }).then(
                    () => pollTask(store, resolve, reject),
                    response => failTask(response, store, resolve, reject),
                );
            });
        },

        stop(store) {
            if (store.state.status === null) {
                return new Promise((resolve, reject) => {
                    reject();
                });
            }

            return Vue.http.patch('api/task', { status: 'terminated' });
        },

        deleteCurrent(store) {
            return Vue.http.delete('api/task').then(
                () => {
                    store.commit('setStatus', null);
                    store.commit('setCurrent', null);
                },
                (response) => {
                    if (response.status !== 400) {
                        throw response;
                    }

                    store.commit('setStatus', null);
                    store.commit('setCurrent', null);
                },
            );
        },
    },
};
