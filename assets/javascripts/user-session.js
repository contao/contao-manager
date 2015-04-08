/**
 * This file is part of tenside/ui.
 *
 * (c) Christian Schiffler <https://github.com/discordier>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/ui
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Christian Schiffler <https://github.com/discordier>
 * @link       https://github.com/tenside/ui
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @filesource
 */

(function(){
    var userSession = angular.module('user-session', []);

    userSession.constant('AUTH_EVENTS', {
        loginSuccess: 'auth-login-success',
        loginFailed: 'auth-login-failed',
        logoutSuccess: 'auth-logout-success',
        sessionTimeout: 'auth-session-timeout',
        notAuthenticated: 'auth-not-authenticated',
        notAuthorized: 'auth-not-authorized'
    });
    userSession.constant('USER_ROLES', {
        all: '*',
        admin: 'admin',
        editor: 'editor',
        guest: 'guest'
    });

    /* Authentication service which uses json responses for login.
     *
     * The response to GET and POST (using credentials) is expected to be of this nature:
     *  {
     *    id: "session id."
     *    user: {
     *      id:   "user id",
     *      user: "username"
     *      role: "any of the roles specified in USER_ROLES constant"
     *    }
     *  }
     *
     */
    userSession.factory('AuthService', ['$rootScope', '$http', 'Session', function ($rootScope, $http, Session) {
        var
            baseUrl,
            authService = {};

        authService.setBaseUrl = function(url) {
            baseUrl = url;
        };

        authService.getBaseUrl = function() {
            return baseUrl;
        };

        authService.getSession = function() {
            return Session;
        };

        authService.login = function (credentials) {
            return $http
                .post(baseUrl, credentials)
                .then(function (res) {
                    Session.create(res.data.id, res.data.user.id, res.data.user.role);
                    return res.data.user;
                });
        };

        authService.logout = function () {
            return $http
                .delete(baseUrl)
                .then(function () {
                    Session.destroy();
                });
        };
        authService.ping = function () {
            return $http
                .get(baseUrl)
                .then(function (res) {
                    Session.create(res.data.id, res.data.user.id,
                        res.data.user.role);
                    return res.data.user;
                });
        };

        authService.isAuthenticated = function () {
            return !!Session.userId;
        };

        authService.isAuthorized = function (authorizedRoles) {
            if (!angular.isArray(authorizedRoles)) {
                authorizedRoles = [authorizedRoles];
            }
            return ((authorizedRoles.length == 0) || (authService.isAuthenticated() &&
            authorizedRoles.indexOf(Session.userRole) !== -1));
        };

        $rootScope.authService = authService;

        return authService;
    }]);

    userSession.service('Session', function () {
        this.create = function (sessionId, userId, userRole) {
            this.id = sessionId;
            this.userId = userId;
            this.userRole = userRole;
        };
        this.destroy = function () {
            this.id = null;
            this.userId = null;
            this.userRole = null;
        };
        return this;
    });

    userSession.run(function ($rootScope, $modal, $http, $location, AUTH_EVENTS, USER_ROLES, AuthService) {
        $rootScope.$on('$routeChangeStart', function (event, next, current) {
            var authorizedRoles = [];
            try{
                authorizedRoles = next.data.authorizedRoles;
            } catch(e) {}
            if (!AuthService.isAuthorized(authorizedRoles)) {
                event.preventDefault();
                if (AuthService.isAuthenticated()) {
                    // user is not allowed
                    $rootScope.$broadcast(AUTH_EVENTS.notAuthorized);
                } else {
                    // user is not logged in
                    $rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
                }
            }
        });

        var checked, open;
        var showDialog = function () {
            if (open) {
                return;
            }
            if (!checked) {
                AuthService.ping().then(function (user) {
                    $rootScope.setCurrentUser(user);
                    $rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
                }, function () {
                    $rootScope.$broadcast(AUTH_EVENTS.loginFailed);
                });
                checked = true;

                return;
            }

            var scope = $rootScope.$new();
            scope.login = function (credentials) {
                scope.loginDialog.close(credentials);
                open = false;
            };
            scope.credentials = {
                username: '',
                password: ''
            };
            scope.loginDialog = $modal.open({
                scope: scope,
                templateUrl: 'pages/login.html',
                backdrop: 'static'
            });

            scope.loginDialog.opened.then(function (credentials) { open = true; });
            scope.loginDialog.result.then(function (credentials) {
                AuthService.login(credentials).then(function (user) {
                    $rootScope.setCurrentUser(user);
                    $rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
                }, function () {
                    $rootScope.$broadcast(AUTH_EVENTS.loginFailed);
                });
            }, function () {
                $rootScope.$broadcast(AUTH_EVENTS.loginFailed);
            });
        };

        $rootScope.$on(AUTH_EVENTS.notAuthenticated, showDialog);
        $rootScope.$on(AUTH_EVENTS.sessionTimeout, showDialog);
        $rootScope.currentUser = null;
        $rootScope.userRoles = USER_ROLES;
        $rootScope.isAuthorized = AuthService.isAuthorized;

        $rootScope.setCurrentUser = function (user) {
            $rootScope.currentUser = user;
        };
        $rootScope.logout = function() {
            AuthService.logout().then(
                function() {
                    $location.path('/');
                }
            );
            $rootScope.setCurrentUser(null);
        };
        $rootScope.login = function() {
            if (!AuthService.isAuthenticated()) {
                $rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
            }
        };

        // Initialize the session as we might still be logged in from a previous load.
        AuthService.ping().then(function (user) {
            $rootScope.setCurrentUser(user);
            $rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
        });
    });

    userSession.config(function ($httpProvider) {
        $httpProvider.interceptors.push([
            '$injector',
            function ($injector) {
                return $injector.get('AuthInterceptor');
            }
        ]);
    });

    userSession.factory('AuthInterceptor', function ($rootScope, $q, AUTH_EVENTS) {
        return {
            responseError: function (response) {
                $rootScope.$broadcast({
                    401: AUTH_EVENTS.notAuthenticated,
                    403: AUTH_EVENTS.notAuthorized,
                    419: AUTH_EVENTS.sessionTimeout,
                    440: AUTH_EVENTS.sessionTimeout
                }[response.status], response);
                return $q.reject(response);
            }
        };
    });

    userSession.directive('formAutofillFix', function ($timeout) {
        return function (scope, element, attrs) {
            element.prop('method', 'post');
            if (attrs.ngSubmit) {
                $timeout(function () {
                    element
                        .unbind('submit')
                        .bind('submit', function (event) {
                            event.preventDefault();
                            element
                                .find('input, textarea, select')
                                .trigger('input')
                                .trigger('change')
                                .trigger('keydown');
                            scope.$apply(attrs.ngSubmit);
                        });
                });
            }
        };
    });
}());
