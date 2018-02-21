/* eslint-disable no-param-reassign */

import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        installed: null,
        add: {},
        change: {},
        remove: [],
    },

    mutations: {
        setInstalled(state, packages) {
            state.installed = packages;
        },

        add(state, pckg) {
            Vue.set(state.add, pckg.name, pckg);
        },

        update(state, { name, version }) {
            Vue.set(state.change, name, version);
        },

        remove(state, name) {
            if (!state.remove.includes(name)) {
                state.remove.push(name);
            }
        },

        restore(state, name) {
            Vue.delete(state.add, name);
            Vue.delete(state.change, name);

            if (state.remove.includes(name)) {
                state.remove.splice(state.remove.indexOf(name), 1);
            }
        },

        reset(state) {
            state.add = {};
            state.change = {};
            state.remove = [];
        },
    },

    actions: {
        list({ state, commit }, cache = true) {
            if (cache && state.installed) {
                return new Promise(resolve => resolve(state.installed));
            }

            return Vue.http.get('api/packages').then(
                (response) => {
                    const packages = Object.assign(
                        {},
                        { 'contao/manager-bundle': response.body['contao/manager-bundle'] },
                        response.body,
                    );

                    commit('setInstalled', packages);

                    return packages;
                },
            );
        },
    },
};
