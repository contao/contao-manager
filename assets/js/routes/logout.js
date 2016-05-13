'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const Login         = require('./../components/routes/login.js');
const TaskPopup     = require('./../components/taskpopup.js');
const requestHelper = require('./../helpers/request.js');

module.exports = {
    path: '/{locale}/logout',
    controller: function(request, routing) {
        requestHelper.setToken('');
        routing.redirect('login');
    }
};




