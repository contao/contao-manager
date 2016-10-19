'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app');
const Install       = require('./../components/routes/install');

module.exports = {
    path: '/{locale}/install',
    preController: [function(results, routing) {
        // Fully configured, never access this route
        if (true === results['tenside_configured']
            && true === results['project_created']
            && true === results['project_installed']
        ) {
            routing.redirect('login');
            return false;
        }

        // Configured but not logged in yet (and not installed or not
        // created - otherwise the if above would have redirected
        // already)
        if (true === results['tenside_configured']
            && false === results['user_loggedIn']
        ) {
            routing.redirect('login');
            return false;
        }

        return true;
    }],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><Install /></App>, document.getElementById('react-container'));
    }
};

