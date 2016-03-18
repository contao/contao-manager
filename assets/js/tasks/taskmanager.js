'use strict';

const eventhandler  = require('./../components/eventhandler.js');
const Task          = require('./task.js');
const jQuery        = require('jQuery');
const Promise       = require('promise');

var checkTaskInstance = function(task) {
    if (!task instanceof Task) {
        throw new Error('Must pass instance of Task!');
    }

    return task;
};

var addTask = function(task) {

    task = checkTaskInstance(task);

    eventhandler.emit('displayTaskPopup', {
        taskTitle: task.getTitle()
    });

    return new Promise(function (resolve, reject) {
        jQuery.ajax('/api/v1/tasks', {
            method: 'POST',
            data: JSON.stringify(task.getPayload()),
            dataType: 'json'
        }).complete(function(response) {
            if ('OK' === response.status) {
                return resolve(response);
            }

            reject(response);

        }).fail(function(err) {
            reject(err);
        });
    });
};

var runNextTask = function(task) {

    task = checkTaskInstance(task);

    return new Promise(function (resolve, reject) {
        jQuery.ajax('/api/v1/tasks', {
            method: 'GET',
            data: JSON.stringify(task.getPayload()),
            dataType: 'json'
        }).complete(function(response) {
            if ('OK' === response.status) {
                return resolve(response);
            }

            reject(response);

        }).fail(function(err) {
            reject(err);
        });
    });
};

var deleteTask = function(task) {

    task = checkTaskInstance(task);

    return new Promise(function (resolve, reject) {
        jQuery.ajax('/api/v1/tasks/' + task.getId(), {
            method: 'DELETE'
        }).complete(function(response) {
            if ('OK' === response.status) {
                return resolve(response);
            }

            reject(response);

        }).fail(function(err) {
            reject(err);
        });
    });
};

var getTask = function(task) {

    task = checkTaskInstance(task);

    return new Promise(function (resolve, reject) {
        jQuery.ajax('/api/v1/tasks/' + task.getId(), {
            method: 'GET',
            dataType: 'json'
        }).complete(function(response) {
            if ('OK' === response.status) {
                task.setContent(response);

                return resolve(response);
            }

            reject(response);

        }).fail(function(err) {
            reject(err);
        });
    });
};

module.exports = {
    addTask: addTask,
    runNextTask: runNextTask,
    deleteTask: deleteTask,
    getTask: getTask
};
