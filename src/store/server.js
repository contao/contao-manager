/* eslint-disable no-param-reassign */

import composer from './server/composer';
import config from './server/config';
import contao from './server/contao';
import database from './server/database';
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
        database,
        opcache,
        phpinfo,
        'php-cli': phpCli,
        'php-web': phpWeb,
        'self-update': selfUpdate,
    },
};
