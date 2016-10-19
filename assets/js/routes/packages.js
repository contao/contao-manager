import React from 'react';
import ReactDOM    from 'react-dom';
import App         from './../components/app';
import Packages    from './../components/routes/packages';
import routeChecks from './../helpers/common-route-checks';
import * as taskmanager from './../helpers/taskmanager';

module.exports = {
    path: '/{locale}/packages',
    preController: routeChecks,
    controller: function(request, routing) {
        taskmanager.deleteOrphanTasks();
        taskmanager.runNextTask();
        ReactDOM.render(<App routing={routing}><Packages /></App>, document.getElementById('react-container'));
    }
};
