'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const App           = require('./../components/app.js');
const Packages      = require('./../components/routes/packages.js');
const routeChecks   = require('./../helpers/common-route-checks.js');

module.exports = {
    path: '/{locale}/packages',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><Packages /></App>, document.getElementById('app'));
    }
};




