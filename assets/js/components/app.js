'use strict';

const React         = require('react');
const Install       = require('./public/install.js');
const Login         = require('./public/login.js');
const Packages      = require('./manager/packages.js');
const File          = require('./manager/file.js');

var AppComponent = React.createClass({

    render: function() {
        switch (this.props.route)  {
            case 'install':
                return <Install />;

            case 'packages':
                return <Packages />;

            case 'app-kernel':
                return <File fileName="AppKernel.php" />;

            case 'composer-json':
                return <File fileName="composer.json" />;

            default:
                return <Login />;
        }
    }
});

module.exports = AppComponent;
