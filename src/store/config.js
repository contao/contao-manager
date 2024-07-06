/* eslint-disable no-param-reassign */

import composer from './config/composer';
import manager from './config/manager';

export default {
    namespaced: true,

    modules: {
        composer,
        manager
    },
};
