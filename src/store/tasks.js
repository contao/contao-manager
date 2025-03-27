/* eslint-disable no-param-reassign */

import axios from 'axios';

let handleTask;
let failTask;
let queue;
let pending = 0;
let ignoreErrors = false;

const pollTask = (store, resolve, reject, delay = 5000, attempt = 1) => {
    setTimeout(() => {
        axios
            .get('api/task', { timeout: 5000 * attempt })
            .then((response) => handleTask(response, store, resolve, reject))
            .catch((error) => failTask(error, store, resolve, reject));
    }, delay);
};

handleTask = (response, store, resolve, reject) => {
    pending = 0;

    if (response.status === 204) {
        resolve();
        return;
    }

    if (!(response.data instanceof Object)) {
        if (!ignoreErrors) {
            store.commit('apiError', response, { root: true });
        }
        reject();
        return;
    }

    const task = response.data;

    store.commit('setCurrent', task);

    switch (task.status) {
        case 'paused':
            // Do nothing, let the promise/queue keep running until the task is continued or aborted
            break;

        case 'active':
        case 'aborting':
            pollTask(store, resolve, reject);
            break;

        case 'terminated': // BC
        case 'complete':
            if (task.autoclose && window.localStorage.getItem('contao_manager_autoclose') === '1') {
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

failTask = (error, store, resolve, reject) => {
    // Request has timed out
    if (error.request && !error.response) {
        pending += 1;

        if (pending <= 5) {
            pollTask(store, resolve, reject, 5000, pending + 1);
            return;
        }
    }

    if (!ignoreErrors) {
        store.commit('setStatus', 'failed');
    }

    reject();
};

export default {
    namespaced: true,

    state: {
        status: null,
        current: null,

        deleting: false,
        initialized: false,
    },

    mutations: {
        setStatus(state, status) {
            state.status = status;
        },

        setCurrent(state, task) {
            state.deleting = false;
            state.current = task;
            state.status = task ? task.status : null;
        },

        setDeleting(state, value) {
            state.deleting = !!value;
        },

        setInitialized(state, value) {
            state.initialized = !!value;
        },
    },

    actions: {
        async init(store) {
            if (store.state.initialized) {
                return Promise.resolve();
            }

            if (queue) {
                return queue.promise;
            }

            queue = Promise.withResolvers();

            const init = () => {
                store.commit('setInitialized', true);
                queue.resolve();
                queue = null;
            };

            pollTask(store, init, init);

            return queue.promise;
        },

        execute(store, task) {
            if (store.state.status !== null) {
                return Promise.reject();
            }

            queue = Promise.withResolvers();

            ignoreErrors = !!task.ignoreErrors;
            delete task.ignoreErrors;

            if (ignoreErrors) {
                // Vue.http.interceptors.unshift((request, next) => {
                //     next((response) => {
                //         if (request.url.substring(0, 4) === 'api/'
                //             && response.headers.get('Content-Type') !== 'application/json'
                //             && response.status >= 400
                //             && response.status <= 599
                //         ) {
                //             throw response.data;
                //         }
                //     })
                // });
            }

            store.commit('setCurrent', task);
            store.commit('setStatus', 'created');

            axios
                .put('api/task', task)
                .then((response) => handleTask(response, store, queue.resolve, queue.reject))
                .catch((error) => failTask(error, store, queue.resolve, queue.reject));

            return queue.promise;
        },

        abort(store) {
            if (store.state.status === null) {
                return Promise.reject();
            }

            store.commit('setStatus', 'aborting');

            if (!queue) {
                queue = Promise.withResolvers();
            }

            axios
                .patch('api/task', { status: 'aborting' })
                .then((response) => handleTask(response, store, queue.resolve, queue.reject))
                .catch((error) => failTask(error, store, queue.resolve, queue.reject));

            return queue.promise;
        },

        continue(store) {
            if (!queue || !store.state.current?.continuable) {
                return Promise.reject();
            }

            store.commit('setStatus', 'active');

            axios
                .patch('api/task', { status: 'active' })
                .then((response) => handleTask(response, store, queue.resolve, queue.reject))
                .catch((error) => failTask(error, store, queue.resolve, queue.reject));
        },

        async deleteCurrent({ commit, dispatch }, retry = 2) {
            commit('setDeleting', true);

            if (queue) {
                queue.resolve();
                queue = null;
            }

            try {
                await axios.delete('api/task');
                commit('setCurrent', null);
                await dispatch('server/contao/get', false, { root: true });
            } catch (response) {
                // Bad request, there are no tasks
                if (response.status === 400) {
                    commit('setCurrent', null);
                    return;
                }

                if (response.status === 403 && retry > 0) {
                    return new Promise((resolve) => {
                        setTimeout(() => {
                            resolve(dispatch('deleteCurrent', retry - 1));
                        }, 5000);
                    });
                }

                if (response.headers.get('Content-Type') === 'application/problem+json') {
                    commit('setError', response.data, { root: true });
                } else {
                    commit(
                        'setError',
                        {
                            type: 'about:blank',
                            status: response.status,
                            response,
                        },
                        { root: true },
                    );
                }

                throw response;
            }
        },
    },
};
