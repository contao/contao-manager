'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app.js');
const Packages      = require('./../components/routes/packages.js');
const routeChecks   = require('./../helpers/common-route-checks.js');
const taskmanager   = require('./../helpers/taskmanager.js');

module.exports = {
    path: '/{locale}/packages',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        taskmanager.deleteOrphanTasks();
        ReactDOM.render(<App routing={routing}><Packages /></App>, document.getElementById('react-container'));
    }
};
