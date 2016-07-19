'use strict';

const React         = require('react');
const Trappings     = require('../trappings/main.js');
const taskmanager   = require('../../helpers/taskmanager.js');

var MaintenanceComponent = React.createClass({

    getInitialState: function() {
        return {};
    },

    handleCacheClear: function() {
        var task = {'type': 'contao-cache-clear'};
        taskmanager.addTask(task).then(taskmanager.runNextTask);
    },

    render: function() {

        return (
            <Trappings>
                <h2>Cache clear and warmup</h2>
                <p>This action will clear the cache for both, the development (dev) as well as the production (prod) environment. It will automatically warm it up again after it has been cleared.</p>
                <button onClick={this.handleCacheClear}>Execute</button>
            </Trappings>
        );
    }
});

module.exports = MaintenanceComponent;
