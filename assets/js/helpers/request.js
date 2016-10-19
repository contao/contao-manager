import xhr      from 'xhr';
import cookie   from 'cookie';
import Promise  from 'bluebird';
import merge    from 'lodash/merge';

let cookies, apiBaseUrl = '';

// Enable cancelling of promises
Promise.config({cancellation: true});

export function createRequest(url, props) {

    props = props || {};

    if ('' !== getToken()) {
        if (undefined === props.headers) {
            props.headers = {};
        }

        props.headers['Authorization'] = 'Bearer ' + getToken();
    }

    props = merge({}, {uri: apiBaseUrl + url, json: true}, props);

    return new Promise(function(resolve, reject, onCancel) {
        let req = xhr(props, function (err, resp) {
            if (null === err) {
                if (resp.headers && resp.headers.authentication) {
                    setRequestToken(resp.headers.authentication);
                }

                resolve(resp);
            } else {
                reject(err);
            }
        });

        onCancel(function() { req.abort(); });
    });
}

export function setRequestToken(token) {
    document.cookie = cookie.serialize(
        'cpm:token', token, {
            domain: window.location.hostname,
            secure: window.location.protocol === 'https:'
        });

    // reset cookie cache
    cookies['cpm:token'] = token;
}

export function getToken() {

    let token = _readCookie('cpm:token');
    if (token) {
        return token;
    }

    return '';
}

export function setApiBaseUrl(newBaseUrl) {
    apiBaseUrl = newBaseUrl;
}

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
