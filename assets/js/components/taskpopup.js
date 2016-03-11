'use strict';

const React         = require('react');
const jQuery        = require('jquery');
const Translation   = require('./translation.js');
const eventhandler  = require('./eventhandler');

var TaskPopupComponent = React.createClass({

    popup: null,

    getInitialState: function() {
        return {
            show: false,
            showConsole: false,
            status: 'loading',
            content: {
                taskTitle: '',
                h2: '',
                shortExplanation: '',
                consoleOutput: ''
            }
        };
    },

    componentDidMount: function() {
        var self = this;
        this.popup = jQuery('#task-popup');

        eventhandler.on('displayTaskPopup', function(state) {
            state.show = true;
            self.setState(state);
        });
        eventhandler.on('hideTaskPopup', function() {
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

        var cssClasses = [];

        if (this.state.showConsole) {
            cssClasses.push('console');
        }

        cssClasses.push('status-' + this.state.status);
        cssClasses = cssClasses.join(' ');

        return (
            <div id="task-popup" className={cssClasses ? cssClasses : ''}>
                <h1>{this.state.content.taskTitle}</h1>

                <div className="status success"><i className="icono-checkCircle"></i></div>
                <div className="status error"><i className="icono-crossCircle"></i></div>
                <div className="status loading">
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
