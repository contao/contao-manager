'use strict';

const React         = require('react');
const ReactDOM      = require('react-dom');
const File          = require('./../components/routes/file.js');
const TaskPopup     = require('./../components/taskpopup.js');
const routeChecks   = require('./../helpers/common-route-checks.js');

// Load php highlight mode
require('codemirror/mode/javascript/javascript');

module.exports = {
    path: '/{locale}/files/composer-json',
    preController: [routeChecks.ifTensideNotOkRedirectToInstall, routeChecks.ifUserNotLoggedInRedirectToLogin],
    controller: function() {
        ReactDOM.render(<File apiEndpoint="/api/v1/composer.json" options={{ mode: {name: "javascript", json: true}, indentUnit: 4 }} />, document.getElementById('app'));
        ReactDOM.render(<TaskPopup />, document.getElementById('popup'));
    }
};




