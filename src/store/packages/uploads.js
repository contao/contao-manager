import Vue from 'vue';

export default {
    namespaced: true,

    state: {
        uploads: null,
        uploading: false,
        files: [],
    },

    getters: {
        hasUploads: (state, getter) => getter.totalUploads > 0,
        totalUploads: state => state.uploads && Object.keys(state.uploads).length || 0,
        canConfirmUploads: state => state.uploads && Object.values(state.uploads).find(
            item => !item.success || item.error
        ) === undefined || false,
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
