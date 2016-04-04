'use strict';

const hashchange   = require('hashchange');
const React        = require('react'); // has to be present here because JSX is transformed to React.createElement()
const ReactDOM     = require('react-dom');
const App          = require('./components/app.js');


function handleRoute(hash) {
    var app = <App route={hash} />;
    ReactDOM.render(app, document.getElementById('app'));
}

hashchange.update(handleRoute);
hashchange.update();
