'use strict';

const request       = require('./request.js');
const Promise       = require('bluebird');

var getState = function() {
    return new Promise(function (resolve, reject) {
        request.createRequest('/api/v1/install/get_state', {
            dataType: 'json'
        }).success(function (response) {
            if ('OK' === response.status) {
                resolve(response.state)
            } else {
                reject(response);
            }

        }).fail(function (err) {
            reject(err);
        });
    });
};

var getLoggedIn = function() {
    return new Promise(function (resolve, reject) {
        request.createRequest('/api/v1/auth', {
            dataType: 'json'
        }).success(function (response) {
            if ('OK' === response.status) {
                resolve({user_loggedIn: true});
            } else {
                resolve({user_loggedIn: false});
            }
        }).fail(function () {
            resolve({user_loggedIn: false});
        });
    });
};

module.exports = {
    getState: getState,
    getLoggedIn: getLoggedIn
};
