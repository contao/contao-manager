import eventhandler from './eventhandler';
import request      from './request';
import forIn        from 'lodash/forIn';

function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

export default {
    runNextTask: function() {
        return request.createRequest('/api/v1/tasks/run')
            .then(function(response) {
                if ('OK' === response.body.status) {
                    eventhandler.emit('displayTaskPopup', {
                        taskId: response.body.task.id
                    });
                }

                return response;
            })
            .catch(function (err) {
                // @todo
            });
    },

    addTask: function(task) {
        return request.createRequest('/api/v1/tasks', {
            method: 'POST',
            json: task
        });
    },

    deleteTask: function(taskId) {
        return request.createRequest('/api/v1/tasks/' + taskId, {
            method: 'DELETE'
        });
    },

    getTask: function(taskId) {
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

                return response;
            })
            .catch(function() {
                // noop
            });
    }
};
