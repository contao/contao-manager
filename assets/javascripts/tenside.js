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
 * @author     Tristan Lins <https://github.com/tristanlins>
 * @author     Tim Becker <https://github.com/tim-bec>
 * @author     Christian Schiffler <https://github.com/discordier>
 * @copyright  Tristan Lins <https://github.com/tristanlins>
 * @link       https://github.com/tenside/ui
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @filesource
 */

var TENSIDE;
var TENSIDEApi = TENSIDEApi || '';

(function () {
    var app = angular.module('tenside', ['ngRoute', 'ui.bootstrap', 'user-session']);

    TENSIDE = app;

    TENSIDE.run(function(AuthService) {
        AuthService.setBaseUrl(TENSIDEApi + 'auth');
    });
    TENSIDE.config(function ($routeProvider, $locationProvider, USER_ROLES, $httpProvider) {
        $locationProvider.html5Mode(false);

        // route for config page
        $routeProvider.when('/config', {
            templateUrl: 'pages/config.html',
            controller: 'tensideConfigController',
            data: {
                authorizedRoles: [USER_ROLES.admin]
            }
        });

        // route for about page
        $routeProvider.when('/about', {
            templateUrl: 'pages/about.html',
            controller: 'tensideAboutController'
        });

        // route for support page
        $routeProvider.when('/support', {
            templateUrl: 'pages/support.html',
            controller: 'tensideSupportController'
        });

        $routeProvider.otherwise({redirectTo: '/about'});

        $httpProvider.interceptors.push(function($q, $rootScope) {
            return {
                'request': function (config) {
                    $rootScope.loading = true;
                    return config;
                },
                'requestError': function (rejection) {
                    $rootScope.loading = false;
                    return $q.reject(rejection);
                },
                'response': function (response) {
                    $rootScope.loading = false;
                    return response;
                },
                'responseError': function (response) {
                    $rootScope.loading = false;
                    return $q.reject(response);
                }
            };
        });
    })
    ;

    app.controller('tensideConfigController', ['$window', '$scope', function ($window, $scope) {
        $scope.config = {};
    }]);

    app.controller('tensideAboutController', ['$window', '$scope', function ($window, $scope) {
        $scope.config = {};
    }]);

    app.controller('tensideSupportController', ['$window', '$scope', function ($window, $scope) {
        $scope.config = {};
    }]);
})();
