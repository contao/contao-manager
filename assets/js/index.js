'use strict';

const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const Promise      = require('bluebird');
const routing      = require('./components/helpers/routing.js');
const App          = require('./components/app.js');
const TaskPopup    = require('./components/taskpopup.js');
const TensideState = require('./components/helpers/tenside-state.js');
const request      = require('./components/helpers/request.js');
const _            = require('lodash');

var router = routing.getRouter();

// Route matched
router.routed.add(function(request, data) {

    // Route requirement promises
    var requirementsPromises = [
        // Tenside state
        TensideState.getState(),
        // Logged in or not?
        TensideState.getLoggedIn()
    ];

    // Wait for requirement promises to resolve
    Promise.all(requirementsPromises).then(function(resolvedPromises) {

        var promiseResults = {};
        _.forIn(resolvedPromises, function(v, k) {
            _.merge(promiseResults, v);
        });

        // Now compare requirement with results
        if (undefined !== data.route.requirement && _.isFunction(data.route.requirement)) {

            var fulfilled = data.route.requirement(promiseResults);
            // Redirect to returned route if not fulfilled
            if (true !== fulfilled) {
                routing.redirect(fulfilled);
                return;
            }
        }

        // Cool, all requirements have been fulfilled, access
        routing.setCurrentRoute(data.route.name);
        ReactDOM.render(<App route={data.route.name} />, document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    });
});

// If no route matched, go to packages by default
router.bypassed.add(function() {
    routing.redirect('packages');
});

// Listen to the history change and call the router then
routing.getHistory().listen(function(location) {
    router.parse(location.pathname);
});

// Dispatch the routing on the initial call
router.parse(document.location.pathname);
