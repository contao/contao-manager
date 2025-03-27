/* eslint-disable no-param-reassign */

import axios from 'axios';

export default {
    namespaced: true,

    state: {
        cache: null,
    },

    mutations: {
        setCache(state, value) {
            state.cache = value;
        },
    },

    actions: {
        get({ state, commit }, cache = true) {
            if (cache && state.cache) {
                return new Promise((resolve) => {
                    resolve(state.cache);
                });
            }

            return axios
                .get('api/server/self-update')
                .then((response) => response.data)
                .catch((error) => {
                    if (error.response?.status === 501) {
                        return {
                            current_version: null,
                            latest_version: null,
                            channel: 'dev',
                            supported: false,
                            error: null,
                        };
                    }

                    throw error;
                })
                .then((result) => {
                    commit('setCache', result);

                    return result;
                });
        },

        async latest() {
            const response = await axios.get('https://download.contao.org/contao-manager/stable/contao-manager.version');

            return response.data.version;
        },
    },
};
