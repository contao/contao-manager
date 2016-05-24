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
            <div className="inner">
                <div id="content">{this.props.children}</div>
                <TaskPopup />
            </div>
        )
    }
});

module.exports = AppComponent;
