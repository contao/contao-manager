import Vue from 'vue';

export default {
	namespaced: true,

	state: {
		cache: null,
		supported: false,
		hasUser: false,
	},

	mutations: {
		setCache(state, response) {
			state.cache = response;
			state.supported = false;
			state.hasUser = false;

			if (response.status === 200) {
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

		set(store, data) {
			return Vue.http.post('api/server/admin-user', data).then(
				response => response,
				response => response
			);
		}
	},
};
