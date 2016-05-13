'use strict';

const React          = require('react');
const ReactDOM       = require('react-dom');
const SelfTest       = require('./../components/routes/selftest.js');
const BoxedTrappings = require('./../components/trappings/boxed.js');
const TaskPopup      = require('./../components/taskpopup.js');
const routeChecks    = require('./../helpers/common-route-checks.js');

module.exports = {
    path: '/{locale}/config/self-test',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function() {
        ReactDOM.render(<BoxedTrappings wide={true}><section><SelfTest /></section></BoxedTrappings>, document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    }
};
