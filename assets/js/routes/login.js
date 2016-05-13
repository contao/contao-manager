'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const Login         = require('./../components/routes/login.js');
const TaskPopup     = require('./../components/taskpopup.js');

module.exports = {
    path: '/{locale}/login',
    preController: [function(results, routing) {
        // Already logged in
        if (true === results['user_loggedIn']) {
            routing.redirect('packages');
        }
    }],
    controller: function() {
        ReactDOM.render(<Login /> , document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    }
};




