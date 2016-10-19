'use strict';

import requestHelper from './../helpers/request';

module.exports = {
    path: '/{locale}/logout',
    controller: function(request, routing) {
        requestHelper.setToken('');
        routing.redirect('login');
    }
};
