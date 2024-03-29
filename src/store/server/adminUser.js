import Vue from 'vue';

export default {
	namespaced: true,

	state: {
		cache: null,
		supported: false,
		hasUser: null,
	},

	mutations: {
		setCache(state, response) {
			state.cache = response;
			state.supported = false;
			state.hasUser = null;

			if (response && (response.status === 200 || response.status === 201)) {
				state.supported = true;
				state.hasUser = !!response.body.hasUser;
			}
		},
	},

	actions: {
		get({ state, commit }, cache = true) {
			if (cache && state.cache) {
				return new Promise((resolve) => {
					resolve(state.cache);
				});
			}

			const handle = (response) => {
				commit('setCache', response);

				return response;
			};

			return Vue.http.get('api/server/admin-user').then(handle, handle);
		},

		set({ commit }, data) {
			const handle = (response) => {
				commit('setCache', response);

				return response;
			};

			return Vue.http.post('api/server/admin-user', data).then(handle, handle);
		}
	},
};
