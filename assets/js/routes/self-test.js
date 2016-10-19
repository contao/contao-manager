'use strict';

const React          = require('react');
const ReactDOM       = require('react-dom');
const App           = require('./../components/app');
const SelfTest       = require('./../components/routes/selftest');
const routeChecks    = require('./../helpers/common-route-checks');

module.exports = {
    path: '/{locale}/config/self-test',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><SelfTest /></App>, document.getElementById('react-container'));
    }
};
