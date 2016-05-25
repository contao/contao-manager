'use strict';

const React         = require('react');
const TaskPopup     = require('./taskpopup.js');

var AppComponent = React.createClass({
    childContextTypes: {
        routing: React.PropTypes.object
    },

    getChildContext: function() {
        return {routing: this.props.routing};
    },

    render: function() {
        return (
            <div id="app">
                {this.props.children}
                <TaskPopup />
            </div>
        )
    }
});

module.exports = AppComponent;
