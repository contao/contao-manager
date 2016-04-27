'use strict';

const Promise   = require('bluebird');
const routing   = require('./routing.js');
const request   = require('./request.js');


// Enable cancelling of promises
Promise.config({cancellation: true});

var cache = {};

var getTranslationForKey = function(key, data, placeholders) {
    data         = typeof data !== 'undefined' ? data : {};
    placeholders = typeof placeholders !== 'undefined' ? placeholders : {};

    var translation = key;
    if (undefined !== data[key]) {
        translation = data[key];
    }

    // Replace placeholders
    if (placeholders) {
        for (var placeholder in placeholders) {
            if (placeholders.hasOwnProperty(placeholder)) {
                translation = translation.replace('%' + placeholder + '%', placeholders[placeholder]);
            }
        }
    }

    return translation;
};

var fetchData = function(domain, locale) {
    domain = typeof domain !== 'undefined' ? domain : 'messages';
    locale = typeof locale !== 'undefined' ? locale : 'fallback';

    if ('fallback' === locale) {
        locale = routing.getLanguage();
    }

    // Maybe cached?
    if (undefined !== cache[domain] && undefined !== cache[domain][locale]) {
        return Promise.resolve(cache[domain][locale]);
    }

    return request.createRequest('/translation/' + locale + '/' + domain)
        .then(function (response) {
            // Cache
            if (undefined === cache[domain]) {
                cache[domain] = {};
            }
            cache[domain][locale] = response;

            return response;
        });
};

module.exports = {
    fetchData: fetchData,
    getTranslationForKey: getTranslationForKey
};
