'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app.js');
const Maintenance   = require('./../components/routes/maintenance.js');
const routeChecks   = require('./../helpers/common-route-checks.js');
const taskmanager   = require('./../helpers/taskmanager.js');

module.exports = {
    path: '/{locale}/maintenance',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        taskmanager.runNextTask();
        ReactDOM.render(<App routing={routing}><Maintenance /></App>, document.getElementById('react-container'));
    }
};
