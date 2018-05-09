/* eslint-disable no-param-reassign */

import Vue from 'vue';

// const STATUS_ACTIVE = 'active';
// const STATUS_COMPLETE = 'complete';
// const STATUS_ERROR = 'error';
// const STATUS_ABORTING = 'aborting';
// const STATUS_STOPPED = 'stopped';

let handleTask;
let failTask;
let pending = 0;

const pollTask = (store, resolve, reject, delay = 1000) => {
    setTimeout(() => {
        Vue.http.get('api/task', {
            timeout: 2000,
        }).then(
            response => handleTask(response, store, resolve, reject),
            response => failTask(response, store, resolve, reject),
        );
    }, delay);
};

handleTask = (response, store, resolve, reject) => {
    pending = 0;

    if (response.status === 204) {
        resolve();
        return;
    }

    const task = response.body;

    store.commit('setCurrent', task);

    switch (task.status) {
        case 'active':
        case 'aborting':
            pollTask(store, resolve, reject);
            break;

        case 'terminated': // BC
        case 'complete':
            if (window.localStorage.getItem('contao_manager_autoclose') === '1' && !task.audit) {
                store.dispatch('deleteCurrent');
            }
            resolve(task);
            break;

        case 'stopped':
        case 'error':
            reject(task);
            break;

        default:
            reject(task);
            break;
    }
};

failTask = (response, store, resolve, reject) => {
    // Request has timed out
    if (response.status === 0) {
        pending += 1;

        if (pending <= 5) {
            pollTask(store, resolve, reject);
            return;
        }
    }

    // store.commit('setCurrent', null);
    store.commit('setStatus', 'failed');
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
            state.status = task ? task.status : null;
        },
    },

    actions: {
        reload(store) {
            return new Promise((resolve, reject) => {
                if (store.state.status !== null) {
                    reject();
                }

                pollTask(store, resolve, reject, 0);
            });
        },

        execute(store, task) {
            return new Promise((resolve, reject) => {
                if (store.state.status !== null) {
                    reject();
                }

                store.commit('setCurrent', task);
                store.commit('setStatus', 'created');

                Vue.http.put('api/task', task).then(
                    response => handleTask(response, store, resolve, reject),
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

            // TODO implement task stopping
            return Vue.http.patch('api/task', { status: 'terminated' });
        },

        deleteCurrent(store) {
            return Vue.http.delete('api/task').then(
                () => {
                    store.commit('setCurrent', null);
                },
                (response) => {
                    if (response.status !== 400) {
                        throw response;
                    }

                    store.commit('setCurrent', null);
                },
            );
        },
    },
};
