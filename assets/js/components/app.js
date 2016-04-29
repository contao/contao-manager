'use strict';

const React          = require('react');
const Install        = require('./routes/install.js');
const Login          = require('./routes/login.js');
const Packages       = require('./routes/packages.js');
const File           = require('./routes/file.js');
const SelfTest       = require('./routes/selftest.js');
const BoxedTrappings = require('./trappings/boxed.js');
const isEqual        = require('lodash/isEqual');

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

            case 'self-test':
                return <BoxedTrappings wide={true}>
                    <section>
                        <SelfTest />
                    </section>
                </BoxedTrappings>;

            default:
                return <Login />;
        }
    }
});

module.exports = AppComponent;
