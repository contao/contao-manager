'use strict';

const requestHelper = require('./../helpers/request.js');

module.exports = {
    path: '/{locale}/logout',
    controller: function(request, routing) {
        requestHelper.setToken('');
        routing.redirect('login');
    }
};




