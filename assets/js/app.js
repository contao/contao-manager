'use strict';

const crossroads        = require('crossroads');
const React             = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM          = require('react-dom');
const InstallComponent  = require('./components/install.js');

// Install
crossroads.addRoute('/{locale}/install', function() {
    ReactDOM.render(
        <InstallComponent />,
        document.getElementById('install_component')
    );
});



// Dispatch router
crossroads.parse(document.location.pathname);
