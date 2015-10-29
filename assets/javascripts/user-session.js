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
    var userSession = angular.module('user-session', ['tenside-api']);

    userSession.factory('sessionRecoverer',
    ['$q', '$injector',
    function($q, $injector) {
        var showDialog = function () {
            var
                scope = $injector.get('$rootScope').$new(),
                api = $injector.get('$tensideApi');
            scope.login = function (credentials) {
                // FIXME: check selected method and login accordingly.
                api.login(credentials.username, credentials.password).then(function (user) {
                    api.setKey(user.data.token, user.data.store || 'session');
                    scope.loginDialog.close();
                });
            };
            scope.credentials = {
                method: 'basic',
                username: '',
                password: '',
                jwtToken: ''
            };
            scope.loginDialog = $injector.get('$modal').open({
                scope: scope,
                templateUrl: 'pages/login.html',
                backdrop: 'static'
            });

            // return the login dialog promise.
            return scope.loginDialog.result;
        };

        return {
            responseError: function(rejection) {
                // Session has expired
                if (rejection.status == 401){
                    var deferred = $q.defer();

                    // Create a new session (recover the session)
                    // We use login method that logs the user in using the current credentials and
                    // returns a promise
                    showDialog().then(deferred.resolve, deferred.reject);

                    // When the session recovered, make the same backend call again and chain the request
                    return deferred.promise.then(function() {
                        var api = $injector.get('$tensideApi');
                        return api(angular.merge(rejection.config, {headers: {authorization: undefined}}));
                    });
                }

                return $q.reject(rejection);
            }
        };
    }]);

    userSession.config(['$httpProvider', function($httpProvider) {
        $httpProvider.interceptors.push('sessionRecoverer');
    }]);
}());
