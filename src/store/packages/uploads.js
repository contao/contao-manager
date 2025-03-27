import axios from 'axios';

const hasDuplicates = (uploads) => {
    const count = Object.values(uploads).reduce((prev, upload) => {
        prev[upload.hash] = (prev[upload.hash] || 0) + 1;

        return prev;
    }, {});

    return !!Object.values(count).find((v) => v > 1);
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
        isDuplicate: (state) => (id, name) =>
            Object.values(state.uploads).find((v) => v.id !== id && (v.hash === state.uploads[id].hash || state.uploads[v.id]?.package?.name === name)),
        isRemoving: (state) => (id) => state.removing.includes(id),

        totalUploads: (state, get) => (state.uploads ? get.unconfirmedUploads.length : 0),
        unconfirmedUploads: (state) => Object.values(state.uploads).filter((item) => !state.confirmed.includes(item.id)),

        canConfirmUploads: (state, get, rootState, rootGet) =>
            state.uploads
                ? Object.values(state.uploads).find(
                      (item) =>
                          !item.success ||
                          item.error ||
                          (Object.keys(rootState.packages.installed).includes(item.package.name) &&
                              rootState.packages.installed[item.package.name].version === item.package.version) ||
                          (item.package.require &&
                              !rootGet['packages/contaoSupported'](item.package.require['contao/core-bundle'] || item.package.require['contao/manager-bundle'])),
                  ) === undefined && !hasDuplicates(state.uploads)
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

        setConfirmed(state, id) {
            state.confirmed.push(id);
        },

        setUnconfirmed(state, id) {
            axios.delete(state.confirmed, state.confirmed.indexOf(id));
        },

        setRemoving(state, id) {
            state.removing.push(id);
        },

        setRemoved(state, id) {
            state.removing = state.removing.filter((r) => r !== id);
        },
    },

    actions: {
        async load({ commit }) {
            try {
                commit('setUploads', (await axios.get('api/packages/uploads')).data);
            } catch (err) {
                if (err.status !== 501) {
                    throw err;
                }

                commit('setUploads', false);
            }
        },

        async confirm({ state, commit }, id) {
            const pkg = state.uploads[id].package;

            if (!pkg) {
                return;
            }

            if (this.getters['packages/packageInstalled'](pkg.name)) {
                this.commit('packages/change', pkg);
            } else {
                this.commit('packages/add', Object.assign({}, pkg, { constraint: pkg.version }));
            }

            if (pkg.suggest) {
                await Promise.all(
                    Object.keys(pkg.suggest).map(async (name) => {
                        if (!this.getters['packages/packageInstalled'](name)) {
                            const metadata = await this.dispatch('packages/metadata', { name });

                            if (!metadata.contaoConstraint || this.getters['packages/contaoSupported'](metadata.contaoConstraint)) {
                                this.commit('packages/add', { name });
                            }
                        }
                    }),
                );
            }

            commit('setConfirmed', id);
        },

        confirmAll({ state, dispatch }) {
            Object.keys(state.uploads).forEach((id) => dispatch('confirm', id));
        },

        unconfirm({ state, commit }, idOrName) {
            const id = state.confirmed.includes(idOrName)
                ? idOrName
                : Object.keys(state.uploads).find((id) => state.uploads[id].package && state.uploads[id].package.name === idOrName && state.confirmed.includes(id));

            if (!id) {
                return;
            }

            commit('setUnconfirmed', id);

            const pkg = state.uploads[id].package;

            if (pkg && pkg.suggest) {
                Object.keys(pkg.suggest).forEach((name) => {
                    if (this.getters['packages/packageAdded'](name)) {
                        this.commit('packages/restore', name);
                    }
                });
            }
        },

        unconfirmAll({ state, dispatch }) {
            Object.keys(state.uploads).forEach((id) => dispatch('unconfirm', id));
        },

        async remove({ commit, dispatch }, id) {
            commit('setRemoving', id);
            await axios.delete(`api/packages/uploads/${id}`);
            await dispatch('load');
            commit('setRemoved', id);
            await dispatch('unconfirm', id);
        },

        async removeAll({ state, commit, dispatch }) {
            await Promise.all(
                Object.keys(state.uploads).map(async (id) => {
                    if (!state.confirmed.includes(id)) {
                        commit('setRemoving', id);
                        await axios.delete(`api/packages/uploads/${id}`);
                        commit('setRemoved', id);
                    }
                }),
            );
            await dispatch('load');
        },
    },
};
