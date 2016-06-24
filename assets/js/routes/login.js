'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app.js');
const Login         = require('./../components/routes/login.js');

module.exports = {
    path: '/{locale}/login',
    preController: [function(results, routing) {
        // Already logged in
        if (true === results['user_loggedIn']) {
            routing.redirect('packages');
            return false;
        }

        return true;
    }],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><Login /></App>, document.getElementById('react-container'));
    }
};




