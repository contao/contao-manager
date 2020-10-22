import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        enabled: null,
        status: null,
    },

    getters: {
        isLoading: state => state.enabled === null || state.status === null,
        isReady: state => state.enabled && state.status !== null && !!state.status.appVersion,
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
                    const config = await dispatch('server/config/get', null, {root: true});
                    enabled = !!config.cloud?.enabled;
                } catch (err) {
                    enabled = false;
                }

                commit('setEnabled', enabled);
            }

            if (!enabled) {
                return;
            }

            try {
                const response = (await Vue.http.get(
                    'https://www.composer-resolver.cloud/',
                    { responseType: 'json', headers: {'Composer-Resolver-Client': 'contao'} },
                ));

                commit('setStatus', response.body);
            } catch (err) {
                commit('setStatus', {});
            }
        },
    },
};
