'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app.js');
const Install       = require('./../components/routes/install.js');

module.exports = {
    path: '/{locale}/install',
    preController: [function(results, routing) {
        // Fully configured, never access this route
        if (true === results['tenside_configured']
            && true === results['project_created']
            && true === results['project_installed']
        ) {
            return routing.redirect('login');
        }

        // Configured but not logged in yet (and not installed or not
        // created - otherwise the if above would have redirected
        // already)
        if (true === results['tenside_configured']
            && false === results['user_loggedIn']
        ) {
            return routing.redirect('login');
        }
    }],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><Install /></App>, document.getElementById('react-container'));
    }
};

