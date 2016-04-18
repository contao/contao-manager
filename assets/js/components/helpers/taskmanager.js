'use strict';

const eventhandler  = require('./eventhandler.js');
const request       = require('./request.js');

var runNextTask = function() {

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
};

module.exports = {
    runNextTask: runNextTask
};
