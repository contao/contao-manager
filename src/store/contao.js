/* eslint-disable no-param-reassign */

import accessKey from './contao/accessKey';
import installTool from './contao/installTool';
import jwtCookie from './contao/jwtCookie';
import maintenanceMode from './contao/maintenanceMode';
import backup from './contao/backup';

export default {
    namespaced: true,

    modules: {
        'access-key': accessKey,
        backup,
        'install-tool': installTool,
        'jwt-cookie': jwtCookie,
        'maintenance-mode': maintenanceMode,
    },

    state: {
        package: null,
        version: null,
    },

    mutations: {
        installTheme(state, { package: pkg, version }) {
            state.package = pkg;
            state.version = version;
        },
    },

    actions: {
        install({ commit, dispatch }, config) {
            // Reset the state of installed theme
            commit('installTheme', { package: null, version: null });

            const task = {
                name: 'contao/install',
                config,
            };

            return dispatch('config/composer/writeDefaults', null, { root: true }).then(() => dispatch('tasks/execute', task, { root: true }));
        },
    },
};
