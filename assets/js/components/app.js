'use strict';

const React         = require('react');
const Install       = require('./public/install.js');
const Login         = require('./public/login.js');
const Packages      = require('./manager/packages.js');

var AppComponent = React.createClass({

    render: function() {
        switch (this.props.route)  {
            case 'install':
                return <Install />;

            case 'packages':
                return <Packages />;

            default:
                return <Login />;
        }
    }
});

module.exports = AppComponent;
