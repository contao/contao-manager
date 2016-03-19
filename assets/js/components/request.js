'use strict';

const jQuery        = require('jquery');

var jwtToken = '';

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

module.exports = {
    createRequest: createRequest,
    setToken: setToken,
    getToken: getToken
};
