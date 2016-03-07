'use strict';

const crossroads   = require('crossroads');
const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const TaskPopup    = require('./components/taskpopup.js');
const Install      = require('./components/install.js');

// Install
crossroads.addRoute('/{locale}/install', function() {
    ReactDOM.render(
        <Install />,
        document.getElementById('install_component')
    );
});

// The TaskPopup is added in general
ReactDOM.render(
    <TaskPopup />,
    document.getElementById('taskpopup_component')
);

// Dispatch router
crossroads.parse(document.location.pathname);
