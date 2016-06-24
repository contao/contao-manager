'use strict';

const eventhandler  = require('./eventhandler.js');
const request       = require('./request.js');
const forIn         = require('lodash/forIn');

function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

module.exports = {
    runNextTask: function() {
        return request.createRequest('/api/v1/tasks/run')
            .then(function(response) {
                if ('OK' === response.body.status) {
                    eventhandler.emit('displayTaskPopup', {
                        taskId: response.body.task
                    });
                }
            })
            .catch(function (err) {
                // @todo
            });
    },

    deleteTask: function(taskId) {
        return request.createRequest('/api/v1/tasks/' + taskId, {
            method: 'DELETE'
        });
    },

    getTask: function() {
        return request.createRequest('/api/v1/tasks/' + taskId)
    },

    getTaskList: function() {
        return request.createRequest('/api/v1/tasks');
    },

    deleteOrphanTasks: function() {
        // Delete tasks older than a week
        var now = new Date();
        var self = this;

        this.getTaskList()
            .then(function(response) {
                if ('OK' === response.body.status) {
                    forIn(response.body.tasks, function(data, taskId) {
                        var createdAt = Date.parse(data['created_at']);
                        var compare = addDays(createdAt, 7);

                        if (compare < now) {
                            self.deleteTask(taskId);
                        }
                    });
                }
            })
            .catch(function() {
                // noop
            });
    }
};
