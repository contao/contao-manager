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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 224 128">
                        <path d="M219.3 123.2c6.2-6.2 6.2-16.4 0-22.6l-96-96c-6.2-6.2-16.4-6.2-22.6 0l-96 96c-3.1 3.1-4.7 7.2-4.7 11.3 0 4.1 1.6 8.2 4.7 11.3 6.2 6.2 16.4 6.2 22.6 0L112 38.5l84.7 84.7a15.7 15.7 0 0 0 22.6 0z"></path>
                    </svg> <Translation domain="taskpopup">Hide Console Output</Translation>
                </a>
                <a  onClick={this.showConsole} className="show">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 224 128">
                        <path d="M4.7 4.7c-6.2 6.2-6.2 16.4 0 22.6l96 96c6.2 6.2 16.4 6.2 22.6 0l96-96c3.1-3.1 4.7-7.2 4.7-11.3s-1.6-8.2-4.7-11.3c-6.2-6.2-16.4-6.2-22.6 0L112 89.4 27.2 4.7c-6.1-6.3-16.4-6.3-22.5 0z"></path>
                    </svg> <Translation domain="taskpopup">Show Console Output</Translation>
                </a>
                <code>{this.state.content.consoleOutput}</code>
            </div>
        );
    }
});

module.exports = TaskPopupComponent;
