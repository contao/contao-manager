'use strict';

const React         = require('react');
const Install       = require('./public/install.js');
const Login         = require('./public/login.js');

var AppComponent = React.createClass({

    render: function() {

        switch (this.props.route)  {
            case 'install':
                return <Install />;

            default:
                return <Login />;
        }
    }
});

module.exports = AppComponent;
