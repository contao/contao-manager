import React from 'react';
import ReactDOM     from 'react-dom';
import App          from './../components/app';
import Maintenance  from './../components/routes/maintenance';
import routeChecks  from './../helpers/common-route-checks';
import { runNextTask }  from './../helpers/taskmanager';

module.exports = {
    path: '/{locale}/maintenance',
    preController: routeChecks,
    controller: function(request, routing) {
        runNextTask();
        ReactDOM.render(<App routing={routing}><Maintenance /></App>, document.getElementById('react-container'));
    }
};
