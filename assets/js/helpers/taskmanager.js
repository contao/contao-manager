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
            .then(function (response) {
                if ('OK' === response.status) {
                    eventhandler.emit('displayTaskPopup', {
                        taskId: response.task
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

    getTaskList: function() {
        return request.createRequest('/api/v1/tasks');
    },

    deleteOrphanTasks: function() {
        // Delete tasks older than a week
        var now = new Date();
        var self = this;

        this.getTaskList()
            .then(function(result) {
                result['foobar'] = {
                    created_at: "2016-05-22T14:50:54+0000"
                };

                forIn(result, function(data, taskId) {
                    var createdAt = Date.parse(data['created_at']);
                    var compare = addDays(createdAt, 7);

                    if (compare < now) {
                        self.deleteTask(taskId);
                    }
                });
            });
    }
};
