/**
 * This file is part of tenside/ui.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/ui
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tim Becker <tb@westwerk.ac>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/ui
 * @filesource
 */

var TENSIDE;
var TENSIDEApi = TENSIDEApi || '';

(function () {
    "use strict";
    TENSIDE = angular.module(
        'tenside',
        [
            'ui.router', 'ui.bootstrap', 'user-session', 'pascalprecht.translate',
            'tenside-api', 'tenside-install', 'tenside-tasklist', 'tenside-console'
        ]
    );
    TENSIDE
        .run(
            [
                '$tensideApi',
                function ($tensideApi) {
                    $tensideApi.setBaseUrl(TENSIDEApi);
                }
            ]
        )
        // Provide a fancy "loading" circle element.
        .factory('loadingHandler',
            [
                '$q',
                '$rootScope',
                function ($q, $rootScope) {
                    var counter = 0;
                    return {
                        'request': function (config) {
                            $rootScope.loading = (++counter > 0);
                            return config;
                        },
                        'requestError': function (rejection) {
                            $rootScope.loading = (--counter > 0);
                            return $q.reject(rejection);
                        },
                        'response': function (response) {
                            $rootScope.loading = (--counter > 0);
                            return response;
                        },
                        'responseError': function (response) {
                            $rootScope.loading = (--counter > 0);
                            return $q.reject(response);
                        }
                    };
                }
            ]
        )
        .config(
            [
                '$stateProvider', '$urlRouterProvider', '$httpProvider', '$translateProvider', '$tooltipProvider',
                function ($stateProvider, $urlRouterProvider, $httpProvider, $translateProvider, $tooltipProvider) {

                    $httpProvider.interceptors.push('loadingHandler');
                    $translateProvider
                        .useSanitizeValueStrategy('escape')
                        .registerAvailableLanguageKeys(['en', 'de'], {
                            'en*': 'en',
                            'de*': 'de'
                        })
                        .useStaticFilesLoader({
                            prefix: 'l10n/',
                            suffix: '.json'
                        })
                        .usePostCompiling(true)
                        .fallbackLanguage('en')
                        .determinePreferredLanguage();
                    if ('' === $translateProvider.use()) {
                        $translateProvider.preferredLanguage('en')
                    }

                    $urlRouterProvider.otherwise('/');
                    $stateProvider
                    // HOME STATES AND NESTED VIEWS ========================================
                        .state(
                            'login',
                            {
                                url: '/login',
                                templateUrl: 'pages/login.html',
                                controller: 'TensideLoginController'
                            }
                        )
                        // ABOUT PAGE AND MULTIPLE NAMED VIEWS =================================
                        .state(
                            'index',
                            {
                                url: '/',
                                templateUrl: 'pages/index.html',
                                controller: 'TensideIndexController'
                            }
                        )
                        .state(
                            'about',
                            {
                                url: '/about',
                                templateUrl: 'pages/about.html',
                                controller: 'tensideAboutController'
                            }
                        )
                        .state(
                            'support',
                            {
                                url: '/support',
                                templateUrl: 'pages/support.html',
                                controller: 'tensideSupportController'
                            }
                        )
                    ;

                    $tooltipProvider.options({
                        placement: 'top',
                        appendToBody: true
                    });
                }
            ]
        )
        .run(
            [
                '$rootScope', '$translate',
                function ($rootScope, $translate) {
                    $rootScope.$on('$stateChangeSuccess', function (event, toState/*, toParams, fromState, fromParams*/) {
                        $rootScope.appContentClass = toState.name;
                        $translate('title.' + toState.name).then(function (translation) {
                            $rootScope.title = translation;
                        }, function () {
                            $translate('title.base').then(function (translation) {
                                $rootScope.title = translation;
                            })
                        });
                    });
                }
            ]
        )
        .controller('TensideIndexController',
            ['$http', '$state', 'TensideTasks',
                function ($http, $state, TensideTasks) {
                    // Determine the initial state of the application.
                    $http.get(TENSIDEApi + '/api/v1/install/get_state.json').success(function (data) {
                        if (data.installation) {
                            var state;
                            switch (data.installation) {
                                case 'FRESH':
                                case 'PARTIAL':
                                    state = 'install';
                                    break;
                                default:
                                    // Do not start polling in install screen.
                                    TensideTasks.startPolling();
                                    state = 'packages';
                            }
                            $state.go(state);
                        }
                    });
                }
            ]
        )
        .controller(
            'tensideAboutController',
            [
                '$scope',
                function ($scope) {
                    $scope.config = {};
                }
            ]
        )
        .controller(
            'tensideSupportController',
            [
                '$scope',
                function ($scope) {
                    $scope.config = {};
                }
            ]
        );
})();
