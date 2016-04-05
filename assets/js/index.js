'use strict';

const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const routing      = require('./components/helpers/routing.js');
const App          = require('./components/app.js');
const TaskPopup    = require('./components/taskpopup.js');
const TensideState = require('./components/helpers/tenside-state.js');
const request      = require('./components/helpers/request.js');

// Check Tenside state
TensideState.getState()
    .then(function(state) {
        // If not configured, go to the install screen
        if (true !== state.tenside_configured) {
            routing.redirect('install');
            return;
        }

        // If no project was created and not logged in, go to the login screen
        if (true !== state.project_created && '' === request.getToken()) {
            routing.redirect('login');
        }
    })
    .then(function () {
        var router = routing.getRouter();
        var routes = routing.getRoutes();

        for (var routeName in routes) {
            if (routes.hasOwnProperty(routeName)) {
                routes[routeName].matched.add(function() {
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


