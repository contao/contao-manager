'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const Install       = require('./../components/routes/install.js');
const TaskPopup     = require('./../components/taskpopup.js');

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
    controller: function() {
        ReactDOM.render(<Install /> , document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    }
};

