'use strict';

const request       = require('./request.js');

var getState = function() {

    return request.createRequest('/api/v1/install/get_state')
        .then(function (response) {
            if ('OK' === response.status) {
                return response.state;
            }
        });
};

var getLoggedIn = function() {

    return request.createRequest('/api/v1/auth')
        .then(function (response) {
            if ('OK' === response.status) {
                return {user_loggedIn: true, username: response.username};
            } else {
                return {user_loggedIn: false};
            }
        })
        .catch(function () {
            return {user_loggedIn: false};
        });
};

module.exports = {
    getState: getState,
    getLoggedIn: getLoggedIn
};
