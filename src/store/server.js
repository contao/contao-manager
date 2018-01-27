/* eslint-disable no-param-reassign */

import composer from './server/composer';
import config from './server/config';
import contao from './server/contao';
import opcache from './server/opcache';
import phpCli from './server/phpCli';
import phpinfo from './server/phpinfo';
import phpWeb from './server/phpWeb';
import selfUpdate from './server/selfUpdate';

export default {
    namespaced: true,

    modules: {
        composer,
        config,
        contao,
        opcache,
        phpinfo,
        'php-cli': phpCli,
        'php-web': phpWeb,
        'self-update': selfUpdate,
    },

    actions: {
        purgeCache({ commit }) {
            commit('server/composer/setCache', null, { root: true });
            commit('server/config/setCache', null, { root: true });
            commit('server/contao/setCache', null, { root: true });
            commit('server/opcache/setCache', null, { root: true });
            commit('server/phpinfo/setCache', null, { root: true });
            commit('server/php-cli/setCache', null, { root: true });
            commit('server/php-web/setCache', null, { root: true });
            commit('server/self-update/setCache', null, { root: true });
        },
    },
};
