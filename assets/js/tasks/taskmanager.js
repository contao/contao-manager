'use strict';

const eventhandler  = require('./eventhandler');
const Task          = require('./../tasks/task.js');
const jQuery        = require('jQuery');

var checkTaskInstance = function(task) {
    if (!task instanceof Task) {
        throw new Error('Must pass instance of Task!');
    }

    return task;
};

var getErrorHandler = function(handler) {
    return handler || function (response) {
            console.log('An error occured! Payload: ' + JSON.stringify(response))
        };
};

var getSuccessHandler = function(handler) {
    return handler || function () {
            // noop
        };
};

var addTask = function(task, onError, onSuccess) {

    task = checkTaskInstance(task);
    onError = getErrorHandler(onError);
    onSuccess = getSuccessHandler(onSuccess);

    eventhandler.emit('displayTaskPopup', {
        taskTitle: task.getTitle()
    });

    jQuery.ajax('/api/v1/tasks', {
        method: 'POST',
        data: JSON.stringify(task.getPayload()),
        dataType: 'json'
    }).complete(function(response) {
        if ('OK' !== response.status) {
            return onError(response);
        }

        return onSuccess(response);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        onError({
            textStatus: textStatus,
            errorThrown: errorThrown
        });
    });
};

var runNextTask = function(task, onError, onSuccess) {

    task = checkTaskInstance(task);
    onError = getErrorHandler(onError);
    onSuccess = getSuccessHandler(onSuccess);

    jQuery.ajax('/api/v1/tasks', {
        method: 'GET',
        data: JSON.stringify(task.getPayload()),
        dataType: 'json'
    }).complete(function(response) {
        if ('OK' !== response.status) {
            return onError(response);
        }

        return onSuccess(response);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        onError({
            textStatus: textStatus,
            errorThrown: errorThrown
        });
    });
};

var deleteTask = function(task, onError, onSuccess) {

    task = checkTaskInstance(task);
    onError = getErrorHandler(onError);
    onSuccess = getSuccessHandler(onSuccess);

    jQuery.ajax('/api/v1/tasks/' + task.getId(), {
        method: 'DELETE'
    }).complete(function(response) {
        if ('OK' !== response.status) {
            return onError(response);
        }

        return onSuccess(response);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        onError({
            textStatus: textStatus,
            errorThrown: errorThrown
        });
    });
};

var getTask = function(task, onError, onSuccess) {

    task = checkTaskInstance(task);
    onError = getErrorHandler(onError);
    onSuccess = getSuccessHandler(onSuccess);


    jQuery.ajax('/api/v1/tasks/' + task.getId(), {
        method: 'GET',
        dataType: 'json'
    }).complete(function(response) {
        if ('OK' !== response.status) {
            return onError(response);
        }

        task.setContent(response);

        return onSuccess(response);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        onError({
            textStatus: textStatus,
            errorThrown: errorThrown
        });
    });
};

module.exports = {
    addTask: addTask,
    runNextTask: runNextTask,
    deleteTask: deleteTask,
    getTask: getTask
};
