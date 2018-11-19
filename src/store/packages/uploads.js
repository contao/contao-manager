import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        uploads: null,
        uploading: false,
        files: [],
        confirmed: [],
    },

    getters: {
        hasUploads: (state, get) => get.totalUploads > 0,
        totalUploads: (state, get) => state.uploads ? get.unconfirmedUploads.length : 0,

        canConfirmUploads: state => state.uploads && Object.values(state.uploads).find(
            item => !item.success || item.error
        ) === undefined || false,

        unconfirmedUploads: state => Object.values(state.uploads).filter(item => !state.confirmed.includes(item.id)),
    },

    mutations: {
        setUploads(state, value) {
            state.uploads = value;
        },

        setUploading(state, value) {
            state.uploading = !!value;
        },

        setFiles(state, value) {
            state.files = value;
        },

        confirm(state, id) {
            const pkg = state.uploads[id].package;

            if (!pkg) {
                return;
            }

            if (this.getters['packages/packageInstalled'](pkg.name)) {
                this.commit('packages/update', pkg.name);
            } else {
                this.commit('packages/add', pkg);
            }

            state.confirmed.push(id);
        },

        unconfirm(state, name) {
            Object.keys(state.uploads).forEach((id) => {
                if (state.uploads[id].package
                    && state.uploads[id].package.name === name
                    && state.confirmed.includes(id)
                ) {
                    state.confirmed.splice(state.confirmed.indexOf(name), 1);
                }
            });
        },

        confirmAll(state) {
            Object.keys(state.uploads).forEach(id => this.commit('packages/uploads/confirm', id));
        },

        unconfirmAll(state) {
            state.confirmed = [];
        },
    },

    actions: {
        async load({ commit }) {
            commit('setUploads', (await Vue.http.get('api/packages/uploads')).body);
        },

        async remove({ dispatch }, id) {
            await Vue.http.delete(`api/packages/uploads/${id}`);
            await dispatch('load');
        },
    },
};
