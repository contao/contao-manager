'use strict';

const eventhandler  = require('./eventhandler.js');
const request       = require('./request.js');

var runNextTask = function() {

   /* eventhandler.emit('displayTaskPopup', {
        taskTitle: task.getTitle()
    });*/

    return request.createRequest(
        '/api/v1/tasks/run',
        {},
        function(response) {
            if ('OK' === response.status) {
                return resolve(response);
            }

            reject(response);

        },
        function (err) {
            reject(err);
        }
    );
};
module.exports = {
    runNextTask: runNextTask
};
