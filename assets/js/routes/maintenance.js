import React from 'react';
import ReactDOM     from 'react-dom';
import App          from './../components/app';
import Maintenance  from './../components/routes/maintenance';
import routeChecks  from './../helpers/common-route-checks';
import taskmanager  from './../helpers/taskmanager';

module.exports = {
    path: '/{locale}/maintenance',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        taskmanager.runNextTask();
        ReactDOM.render(<App routing={routing}><Maintenance /></App>, document.getElementById('react-container'));
    }
};
