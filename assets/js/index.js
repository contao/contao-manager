'use strict';

const hashchange   = require('hashchange');
const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const App          = require('./components/app.js');
const TensideState = require('./components/tenside/state.js');

// Routing definition
function handleRoute(hash) {

    // Check Tenside state and redirect to install screen if not installed
    TensideState.getState()
        .then(function(state) {
            // If not configured or project not created, go to the install screen
            if (true !== state.tenside_configured || true !== state.project_created) {
                hash = 'install';
                hashchange.updateHash('install');
            }

            var app = <App route={hash} />;
            ReactDOM.render(app, document.getElementById('app'));
        });
}

hashchange.update(handleRoute);
hashchange.update();

