import algoliasearch from 'algoliasearch';

const index = algoliasearch('60DW2LJW0P', '13718a23f4e436f7e7614340bd87d913').initIndex(`v3_packages`);

export default {
    namespaced: true,

    state: {
        language: 'en',
        metadata: {},
    },

    mutations: {
        setLanguage(state, language) {
            state.language = language;
            state.metadata = {};
        },

        cache(state, { name, data }) {
            state.metadata[name] = data;
        },

        reset(state) {
            state.metadata = {};
        },
    },

    actions: {
        async get({ state, commit }, name) {
            if (Object.keys(state.metadata).includes(name)) {
                return state.metadata[name];
            }

            let data;

            try {
                const content = await index.search({
                    filters: `name:"${name}" AND languages:${state.language}`,
                    hitsPerPage: 1,
                });

                data = content.hits[0];
            } catch (err) {
                return null;
            }

            delete data.versions;
            delete data.version;
            delete data.time;
            delete data.constraint;

            commit('cache', { name, data: data });

            return data;
        },

        async find({ state }, params) {
            params.filters = `languages:${state.language} AND dependency:false`;

            return await index.search(params);
        },
    },
};
