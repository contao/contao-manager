/**
 * This file is part of tenside/contao-ui.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/contao-ui
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/contao-ui/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/contao-ui
 * @filesource
 */

(function () {
    "use strict";
    angular
        .module(
            'tenside-login',
            ['tenside-api', 'ui.router']
        )
        .factory(
            'sessionRecoverer',
            ['$q', '$injector',
                function ($q, $injector) {
                    return {
                        responseError: function (rejection) {
                            // Session has expired
                            if (rejection.status == 401) {
                                var
                                    $state = $injector.get('$state'),
                                    $stateParams = $injector.get('$stateParams');

                                if ($state.current.name !== 'login') {
                                    $state.go(
                                        'login',
                                        {
                                            state: $state.current.name,
                                            params: $state.params
                                        },
                                        {reload: true}
                                    );
                                }

                                return $q.reject(rejection);
                            }

                        }
                    };
                }
            ]
        )
        .config(
            [
                '$httpProvider', '$stateProvider',
                function ($httpProvider, $stateProvider) {
                    $httpProvider.interceptors.push('sessionRecoverer');

                    $stateProvider
                        .state('login', {
                            templateUrl: 'pages/login.html',
                            controller: 'tensideLoginController',
                            params: {
                                state: null,
                                params: null
                            }
                        });
                }
            ]
        )
        .controller(
            'tensideLoginController',
            [
                '$scope', '$tensideApi', '$stateParams', '$state',
                function ($scope, $tensideApi, $stateParams, $state) {
                    var
                        previousState = $stateParams.state,
                        previousParams = $stateParams.params;

                    $scope.credentials = {
                        username: '',
                        password: ''
                    };
                    $scope.login = function ()
                    {
                        if (!($scope.credentials.username && $scope.credentials.password)) {
                            return;
                        }

                        $tensideApi
                            .login($scope.credentials.username, $scope.credentials.password)
                            .then(function (user) {
                                $tensideApi.setKey(user.data.token, user.data.store || 'session');

                                $state.go(previousState, previousParams, {reload: true});
                            });
                    };
                }
            ]
        );

    // Late dependency injection
    TENSIDE.requires.push('tenside-login');
}());
