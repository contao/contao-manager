'use strict';

const React         = require('react');
const Navigation    = require('./navigation');
const eventhandler  = require('../../helpers/eventhandler');
const compact       = require('lodash/compact');
const isEqual       = require('lodash/isEqual');

var MainTrappingsComponent = React.createClass({

    getInitialState: function() {
        return {
            blurClass: ''
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {
        eventhandler.on('displayTaskPopup', this.blurIn);
        eventhandler.on('hideTaskPopup', this.blurOut);
    },

    componentWillUnmount: function() {
        eventhandler.removeListener('displayTaskPopup', this.blurIn);
        eventhandler.removeListener('hideTaskPopup', this.blurOut);
    },

    blurIn: function() {
        this.setState({blurClass: 'blur-in'});
    },

    blurOut: function() {
        this.setState({blurClass: 'blur-out'});
    },

    render: function() {

        var classes = [
            'manager',
            this.state.blurClass
        ];

        classes = compact(classes);

        return (
            <div id="content" className={classes.join(' ')}>

                <header>
                    <a id="logo" href="#"><img src="web-assets/images/logo.svg" width="40" height="40" alt="Contao Logo" />Contao Manager</a>
                    <a id="nav-toggle"><span /><span /><span /></a>
                    <Navigation />
                </header>

                <main>
                    {this.props.children}
                </main>

                <footer>
                    Contao Manager v1.0
                    <a href="https://manager.contao.org" target="_blank" className="support">Support</a>
                </footer>

            </div>
        );
    }
});

module.exports = MainTrappingsComponent;
