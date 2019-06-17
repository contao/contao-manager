/* eslint-disable no-param-reassign */

import accessKey from './contao/accessKey';

export default {
    namespaced: true,

    modules: {
        'access-key': accessKey,
    },

    actions: {
        install({ dispatch }, { version, coreOnly, noUpdate }) {
            const task = {
                name: 'contao/install',
                config: {
                    version,
                    'core-only': coreOnly ? '1' : '0',
                    'no-update': noUpdate ? '1' : '0',
                },
            };

            return dispatch('config/composer/writeDefaults', null, { root: true })
                .then(() => dispatch('tasks/execute', task, { root: true }));
        },
    },
};
