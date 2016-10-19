import Promise from 'bluebird';
import { createRequest } from './request';

// Enable cancelling of promises
Promise.config({cancellation: true});

var cache = {};

export function getTranslationForKey(key, data, placeholders) {
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
}

export function fetchTranslationData(domain, locale) {

    var cacheKey = domain + '.' + locale;

    // Check the cache
    if (undefined !== cache[cacheKey]) {
        return cache[cacheKey];
    }

    return cache[cacheKey] = createRequest('/translation/' + locale + '/' + domain);
}
