import eventhandler from './eventhandler';
import { createRequest } from './request';
import forIn        from 'lodash/forIn';

function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

export function runNextTask() {
    return createRequest('/api/v1/tasks/run')
        .then(function(response) {
            if ('OK' === response.body.status) {
                eventhandler.emit('displayTaskPopup', {
                    taskId: response.body.task.id
                });
            }

            return response;
        })
        .catch(function (err) {
            console.log(err);
        });
}

export function addTask(task) {
    return createRequest('/api/v1/tasks', {
        method: 'POST',
        json: task
    });
}

export function deleteTask(taskId) {
    return createRequest('/api/v1/tasks/' + taskId, {
        method: 'DELETE'
    });
}

export function getTask(taskId) {
    return createRequest('/api/v1/tasks/' + taskId)
}

export function deleteOrphanTasks() {
    // Delete tasks older than a week
    var now = new Date();
    var self = this;

    createRequest('/api/v1/tasks')
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
