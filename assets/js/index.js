'use strict';

const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const Promise      = require('bluebird');
const routing      = require('./components/helpers/routing.js');
const App          = require('./components/app.js');
const TaskPopup    = require('./components/taskpopup.js');
const TensideState = require('./components/helpers/tenside-state.js');
const request      = require('./components/helpers/request.js');

// Enable cancelling of promises for the whole app
Promise.config({cancellation: true});

// Check Tenside state
TensideState.getState()
    .then(function(state) {
        // If not configured or project not created or installed, go to the install screen
        if (false === state.tenside_configured
        || false === state.project_created
        || false === state.project_installed) {
            routing.redirect('install');
            return;
        }

        // If not logged in, go to the login screen
        if ('' === request.getToken()) {
            routing.redirect('login');
        }
    })
    .then(function () {
        var router = routing.getRouter();
        var routes = routing.getRoutes();

        for (var routeName in routes) {
            if (routes.hasOwnProperty(routeName)) {
                routes[routeName].matched.add(function() {
                    routing.setCurrentRoute(this.routeName);
                    switchContent(this.routeName);
                }.bind({ routeName: routeName }));
            }
        }

        // Fallback
        router.bypassed.add(function() {
            var target = '' === request.getToken() ? 'login' : 'packages';
            routing.redirect(target);
        });

        routing.getHistory().listen(function(location) {
            router.parse(location.pathname);
        });
        router.parse(document.location.pathname);
    });



var switchContent = function(routeName) {
    ReactDOM.render(<App route={routeName} />, document.getElementById('app'));
    ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
};


