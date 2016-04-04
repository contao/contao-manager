'use strict';

const hashchange   = require('hashchange');
const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const history      = require('history').createHistory();
const App          = require('./components/app.js');
const TensideState = require('./components/tenside/state.js');
const request      = require('./components/request.js');

// Routing definition
function handleRoute(hash) {

    // Check Tenside state
    TensideState.getState()
        .then(function(state) {
            // If not configured, go to the install screen
            if (true !== state.tenside_configured) {
                hash = 'install';
                updateHashWithoutRedirect(hash);
            }

            return state;
        })
        .then(function(state) {
            // If no project was created and not logged in, go to the login screen
            if (true !== state.project_created && '' === request.getToken()) {
                hash = 'login';
                updateHashWithoutRedirect(hash);
            }
        })
        .then(function() {
            var app = <App route={hash} />;
            ReactDOM.render(app, document.getElementById('app'));
        });
}

hashchange.update(handleRoute);
hashchange.update();

function updateHashWithoutRedirect(hash) {
    history.push(window.location.pathname + '#' + hash);
}
