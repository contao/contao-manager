'use strict';

const React         = require('react');
const jQuery        = require('jQuery');
const Translation   = require('./translation.js');
const eventhandler  = require('./eventhandler');

var TaskPopupComponent = React.createClass({

    popup: null,

    getInitialState: function() {
        return {
            show: false,
            showConsole: false,
            content: {
                h1: '',
                h2: '',
                shortExplanation: '',
                consoleOutput: ''
            }
        };
    },

    componentDidMount: function() {
        var self = this;
        this.popup = jQuery('#task-popup');

        eventhandler.on('displayTaskPopup', function(content) {
            self.setState({
                show: true,
                content: content
            });
        });
        eventhandler.on('hideTaskPopup', function(content) {
            self.setState({
                show: false
            });
        });
    },

    componentWillUpdate: function(nextProps, nextState) {

        if (nextState.show) {
            this.popup.fadeIn();
        } else {
            this.popup.fadeOut();
        }
    },

    hideConsole: function() {
        this.setState({showConsole: false});
    },

    showConsole: function() {
        this.setState({showConsole: true});
    },

    render: function() {

        return (
            <div id="task-popup" className={this.state.showConsole ? "console" : ""}>
                <h1>{this.state.content.h1}</h1>

                <div className="loading">
                    <div className="bounce1"></div>
                    <div className="bounce2"></div>
                    <div className="bounce3"></div>
                    <div className="bounce4"></div>
                    <div className="bounce5"></div>
                </div>

                <h2>{this.state.content.h2}</h2>
                <p>{this.state.content.shortExplanation}</p>

                <button>Cancel</button>

                <a onClick={this.hideConsole} className="hide">
                    <i className="icono-caretRight"></i>
                    <Translation domain="taskpopup">Hide Console Output</Translation>
                </a>
                <a onClick={this.showConsole} className="show">
                    <i className="icono-caretRight"></i>
                    <Translation domain="taskpopup">Show Console Output</Translation>
                </a>
                <code>{this.state.content.consoleOutput}</code>
            </div>
        );
    }
});

module.exports = TaskPopupComponent;
