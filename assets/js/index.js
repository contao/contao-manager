'use strict';

const crossroads   = require('crossroads');
const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const App          = require('./components/app.js');
const TaskPopup    = require('./components/taskpopup.js');


// Routes
var routes = [];

// Index
routes['index'] = crossroads.addRoute('/{locale}/', function() {
    ReactDOM.render(
        <App />,
        document.getElementById('app')
    )
    ReactDOM.render(
        <TaskPopup />,
        document.getElementById('popup')
    );
});

// Install
routes['install'] = crossroads.addRoute('/{locale}/install', function() {
    // do stuff
});

// Dispatch router
crossroads.bypassed.add(function(request) {
    var lang = request.replace(/\/([^\/]{2})(.+)/, '$1');
    window.location.href = routes['index'].interpolate({locale: lang});
});
crossroads.parse(document.location.pathname);
