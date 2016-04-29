'use strict';

const jQuery        = require('jquery');
const cookie        = require('cookie');
const Promise       = require('bluebird');

var cookies;

// Enable cancelling of promises
Promise.config({cancellation: true});

var createRequest = function(url, props) {

    props = props || {};

    if ('' !== getToken()) {
        if (undefined === props.headers) {
            props.headers = {};
        }

        props.headers['Authorization'] = 'Bearer ' + getToken();
    }

    return new Promise(function(resolve, reject, onCancel) {
        var req = jQuery.ajax(url, props)
            .done(function(data, textStatus, jqXHR) {
                // Check if response contains a token
                var token = jqXHR.getResponseHeader('Authentication');
                if (token) {
                    setToken(token);
                }

                resolve(data);
            })
            .fail(reject);

        onCancel(function() { req.abort(); });
    });
};

var setToken = function(token) {
    document.cookie = cookie.serialize(
        'cpm:token', token, {
            domain: window.location.hostname,
            secure: window.location.protocol === 'https:'
        });

    // reset cookie cache
    cookies['cpm:token'] = token;
};

var getToken = function() {

    var token = _readCookie('cpm:token');
    if (token) {
        return token;
    }

    return '';
};

function _readCookie(name,c,C,i){
    if(cookies){ return cookies[name]; }

    c = document.cookie.split('; ');
    cookies = {};

    for(i=c.length-1; i>=0; i--){
        C = c[i].split('=');
        cookies[C[0]] = C[1];
    }

    return cookies[name];
}

module.exports = {
    createRequest: createRequest,
    setToken: setToken,
    getToken: getToken
};
