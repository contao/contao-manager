'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const File          = require('./../components/routes/file.js');
const TaskPopup     = require('./../components/taskpopup.js');
const routeChecks   = require('./../helpers/common-route-checks.js');

// Load php highlight mode
require('codemirror/mode/php/php');

module.exports = {
    path: '/{locale}/files/app-kernel',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function() {
        ReactDOM.render(<File apiEndpoint="/api/v1/AppKernel.php" options={{ mode: 'php', indentUnit: 4 }} />, document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    }
};
