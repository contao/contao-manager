'use strict';

import React from 'react';
import TaskPopup from './taskpopup';

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

export default AppComponent;
