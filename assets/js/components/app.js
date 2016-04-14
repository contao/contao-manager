'use strict';

const React         = require('react');
const Install       = require('./public/install.js');
const Login         = require('./public/login.js');
const Packages      = require('./manager/packages.js');
const File          = require('./manager/file.js');

// Load php and javascript for file edit
require('react-codemirror/node_modules/codemirror/mode/javascript/javascript');
require('react-codemirror/node_modules/codemirror/mode/php/php');

var AppComponent = React.createClass({

    render: function() {
        var options = {};

        switch (this.props.route)  {
            case 'install':
                return <Install />;

            case 'packages':
                return <Packages />;

            case 'app-kernel':
                options = { mode: 'php', indentUnit: 4 };
                return <File apiEndpoint="/api/v1/AppKernel.php" options={options} />;

            case 'composer-json':
                options = { mode: {name: "javascript", json: true}, indentUnit: 4 };
                return <File apiEndpoint="/api/v1/composer.json" options={options} />;

            default:
                return <Login />;
        }
    }
});

module.exports = AppComponent;
