'use strict';

import Promise      from 'bluebird';
import routing      from './helpers/routing';
import TensideState from './helpers/tenside-state';
import request      from './helpers/request';
import forIn        from 'lodash/forIn';
import merge        from 'lodash/merge';
import isFunction   from 'lodash/isFunction';
import isArray      from 'lodash/isArray';

var router = routing.getRouter();

request.setApiBaseUrl(routing.getBaseHref());

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
        var renderController = true;

        if (undefined !== data.route.preController && isArray(data.route.preController)) {
            forIn(data.route.preController, function(callback) {
                if (isFunction(callback)) {
                    // No need to check for more callbacks if aborted
                    if (false === renderController) {
                        return null;
                    }

                    renderController = callback(promiseResults, routing);
                }
            });
        }

        if (renderController) {
            // Set the current route
            routing.setCurrentRoute(data.route.name);

            // Call the controller
            data.route.controller(request, routing)
        }

        return null;
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
