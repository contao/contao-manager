'use strict';

const React         = require('react');
const Install       = require('./public/install.js');
const Login         = require('./public/login.js');
const Packages      = require('./manager/packages.js');
const File          = require('./manager/file.js');
const isEqual       = require('lodash/isEqual');

// Load php and javascript for file edit
require('codemirror/mode/javascript/javascript');
require('codemirror/mode/php/php');

var AppComponent = React.createClass({

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    render: function() {
        switch (this.props.route)  {
            case 'install':
                return <Install />;

            case 'packages':
                return <Packages />;

            case 'app-kernel':
                return <File apiEndpoint="/api/v1/AppKernel.php" options={{ mode: 'php', indentUnit: 4 }} />;

            case 'composer-json':
                return <File apiEndpoint="/api/v1/composer.json" options={{ mode: {name: "javascript", json: true}, indentUnit: 4 }} />;

            default:
                return <Login />;
        }
    }
});

module.exports = AppComponent;
