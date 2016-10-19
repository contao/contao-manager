'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app');
const Packages      = require('./../components/routes/packages');
const routeChecks   = require('./../helpers/common-route-checks');
const taskmanager   = require('./../helpers/taskmanager');

module.exports = {
    path: '/{locale}/packages',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        taskmanager.deleteOrphanTasks();
        taskmanager.runNextTask();
        ReactDOM.render(<App routing={routing}><Packages /></App>, document.getElementById('react-container'));
    }
};
