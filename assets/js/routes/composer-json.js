import React       from 'react';
import ReactDOM    from 'react-dom';
import App         from './../components/app';
import File        from './../components/routes/file';
import routeChecks from './../helpers/common-route-checks';

// Load php highlight mode
require('codemirror/mode/javascript/javascript');

export default {
    name: 'composer-json',
    path: '/{locale}/files/composer-json',
    preController: routeChecks,
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><File apiEndpoint="/api/v1/composer.json" options={{ mode: {name: "javascript", json: true}, indentUnit: 4 }} /></App>, document.getElementById('react-container'));
    }
}
