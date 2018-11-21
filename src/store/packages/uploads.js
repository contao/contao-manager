import Vue from 'vue';

const hasDuplicates = (uploads) => {
    const count = Object.values(uploads).reduce(
        (prev, upload) => {
            prev[upload.hash] = (prev[upload.hash] || 0) + 1;

            return prev;
        },
        {},
    );

    return !!Object.values(count).find(v => v > 1);
};

export default {
    namespaced: true,

    state: {
        uploads: null,
        uploading: false,
        files: [],
        confirmed: [],
        removing: [],
    },

    getters: {
        hasUploads: (state, get) => get.totalUploads > 0,
        hasDuplicates: state => hasDuplicates(state.uploads),
        isDuplicate: state => id => Object.values(state.uploads).find(v => v.id !== id && v.hash === state.uploads[id].hash),
        isRemoving: state => id => state.removing.includes(id),

        totalUploads: (state, get) => state.uploads ? get.unconfirmedUploads.length : 0,
        unconfirmedUploads: state => Object.values(state.uploads).filter(item => !state.confirmed.includes(item.id)),

        canConfirmUploads: (state, get) => state.uploads
            ? Object.values(state.uploads).find(item => !item.success || item.error) === undefined
                && !get.hasDuplicates
            : false,
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

        unconfirm(state, idOrName) {
            if (state.confirmed.includes[idOrName]) {
                Vue.delete(state.confirmed, state.confirmed.indexOf(idOrName));
                return;
            }

            Object.keys(state.uploads).forEach((id) => {
                if (state.uploads[id].package
                    && state.uploads[id].package.name === idOrName
                    && state.confirmed.includes(id)
                ) {
                    Vue.delete(state.confirmed, state.confirmed.indexOf(id));
                }
            });
        },

        confirmAll(state) {
            Object.keys(state.uploads).forEach(id => this.commit('packages/uploads/confirm', id));
        },

        unconfirmAll(state) {
            state.confirmed = [];
        },

        setRemoved(state, id) {
            state.removing.push(id);
        },
    },

    actions: {
        async load({ commit }) {
            try {
                commit('setUploads', (await Vue.http.get('api/packages/uploads')).body);
            } catch (err) {
                if (err.status !== 501) {
                    throw err;
                }

                commit('setUploads', false);
            }
        },

        async remove({ commit, dispatch }, id) {
            commit('setRemoved', id);
            await Vue.http.delete(`api/packages/uploads/${id}`);
            await dispatch('load');
            commit('unconfirm', id);
        },

        async removeAll({ state, commit, dispatch }) {
            await Promise.all(Object.keys(state.uploads).map(
                (id) => {
                    commit('setRemoved', id);
                    return Vue.http.delete(`api/packages/uploads/${id}`)
                },
            ));
            await dispatch('load');
            commit('unconfirmAll');
        }
    },
};
