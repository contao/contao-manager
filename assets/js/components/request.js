'use strict';

const jQuery        = require('jquery');

var jwtToken = '';
var username = '';

var createRequest = function(url, props) {

    if ('' !== jwtToken) {
        if (undefined === props.headers) {
            props.headers = {};
        }

        props.headers['Authorization'] = 'Bearer ' + jwtToken;
    }

    return jQuery.ajax(url, props);
};

var setToken = function(token) {
    jwtToken = token;
};

var getToken = function() {
    return jwtToken;
};

var setUsername = function(name) {
    username = name;
};

var getUsername = function() {
    return username;
};

module.exports = {
    createRequest: createRequest,
    setToken: setToken,
    getToken: getToken,
    setUsername: setUsername,
    getUsername: getUsername
};
