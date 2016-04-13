'use strict';

const jQuery        = require('jquery');
const cookie        = require('cookie');

var username = '';
var cookies;

var createRequest = function(url, props) {

    if ('' !== getToken()) {
        if (undefined === props.headers) {
            props.headers = {};
        }

        props.headers['Authorization'] = 'Bearer ' + getToken();
    }

    return jQuery.ajax(url, props);
};

var setToken = function(token) {

    var expires = new Date();
    expires.setTime(expires.getTime() + 10 * 60 * 1000); // 10 minutes

    document.cookie = cookie.serialize(
        'cpm:token', token, {
            expires: expires,
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

var setUsername = function(name) {
    // @todo this has to be improved because currently it's possible to have
    // a token but no username is set to the app because the auth
    // api endpoint does not yet deliver the username.
    // See: https://github.com/tenside/core-bundle/issues/7
    username = name;
};

var getUsername = function() {
    return username;
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
    getToken: getToken,
    setUsername: setUsername,
    getUsername: getUsername
};
