'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app');
const Maintenance   = require('./../components/routes/maintenance');
const routeChecks   = require('./../helpers/common-route-checks');
const taskmanager   = require('./../helpers/taskmanager');

module.exports = {
    path: '/{locale}/maintenance',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        taskmanager.runNextTask();
        ReactDOM.render(<App routing={routing}><Maintenance /></App>, document.getElementById('react-container'));
    }
};
