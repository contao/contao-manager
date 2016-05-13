'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const Packages      = require('./../components/routes/packages.js');
const TaskPopup     = require('./../components/taskpopup.js');
const routeChecks   = require('./../helpers/common-route-checks.js');

module.exports = {
    path: '/{locale}/packages',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function() {
        ReactDOM.render(<Packages /> , document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    }
};




