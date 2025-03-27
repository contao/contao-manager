import axios from 'axios';

export default {
    namespaced: true,

    state: {
        enabled: null,
        status: null,
    },

    getters: {
        isLoading: (state) => state.enabled === null || state.status === null,
        isReady: (state) => state.enabled && state.status !== null && !!state.status.appVersion,
        hasError: (state, getters) => state.enabled && !getters.isLoading && !getters.isReady,
    },

    mutations: {
        setEnabled(state, enabled) {
            state.enabled = enabled;
        },

        setStatus(state, value) {
            state.status = value;
        },
    },

    actions: {
        async fetch({ state, commit, dispatch }) {
            let enabled = state.enabled;

            if (state.enabled === null) {
                try {
                    const config = await dispatch('server/config/get', null, { root: true });
                    enabled = !!config.cloud?.enabled;
                } catch (err) {
                    enabled = false;
                }

                commit('setEnabled', enabled);
            }

            if (!enabled) {
                commit('setStatus', {});
                return;
            }

            try {
                const response = await axios.get('https://www.composer-resolver.cloud/', {
                    timeout: 2500,
                    responseType: 'json',
                    headers: { 'Composer-Resolver-Client': 'contao' },
                });

                if (!response.data?.appVersion) {
                    commit('setStatus', {});
                    return;
                }

                commit('setStatus', response.data);
            } catch (err) {
                commit('setStatus', {});
            }
        },
    },
};
