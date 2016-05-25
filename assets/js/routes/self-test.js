'use strict';

const React          = require('react');
const ReactDOM       = require('react-dom');
const App           = require('./../components/app.js');
const SelfTest       = require('./../components/routes/selftest.js');
const BoxedTrappings = require('./../components/trappings/boxed.js');
const routeChecks    = require('./../helpers/common-route-checks.js');

module.exports = {
    path: '/{locale}/config/self-test',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><BoxedTrappings wide={true}><section><SelfTest /></section></BoxedTrappings></App>, document.getElementById('react-container'));
    }
};
