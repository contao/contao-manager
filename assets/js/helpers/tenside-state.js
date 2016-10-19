import { createRequest } from './request';

export function getState() {

    return createRequest('/api/v1/install/get_state')
        .then(function (response) {
            if ('OK' === response.body.status) {
                return response.body.state;
            }

            return null;
        });
}

export function getLoggedIn() {

    return createRequest('/api/v1/auth')
        .then(function (response) {
            if ('OK' === response.body.status) {
                return {user_loggedIn: true, username: response.body.username};
            }
            return {user_loggedIn: false};
        })
        .catch(function () {
            return {user_loggedIn: false};
        });
}

export function getSelfTest() {
    return getState()
        .then(function(state) {

            var selfTestEndPoint = '/api/v1/selftest';

            if (false === state.project_installed) {
                selfTestEndPoint = '/api/v1/install/selftest';
            }

            return createRequest(selfTestEndPoint)
                .then(function(response) {
                    return response.body.results;
                })
                .catch(function() {
                    return {};
                });
        });
}
