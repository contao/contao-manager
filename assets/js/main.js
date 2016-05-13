'use strict';

const Promise      = require('bluebird');
const routing      = require('./helpers/routing.js');
const TensideState = require('./helpers/tenside-state.js');
const request      = require('./helpers/request.js');
const forIn        = require('lodash/forIn');
const merge        = require('lodash/merge');
const isFunction   = require('lodash/isFunction');
const isArray      = require('lodash/isArray');

var router = routing.getRouter();

// @todo Cleanup tasks older than x days/weeks whatever

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
        forIn(resolvedPromises, function(v, k) {
            merge(promiseResults, v);
        });

        // Check the preController callbacks now
        if (undefined !== data.route.preController && isArray(data.route.preController)) {
            forIn(data.route.preController, function(callback) {
                if (isFunction(callback)) {
                    callback(promiseResults, routing);
                }
            });
        }

        // Set the current route
        routing.setCurrentRoute(data.route.name);

        // Call the controller
        data.route.controller(request, routing)
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
