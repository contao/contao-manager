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
    TENSIDE = angular.module('tenside', ['ngRoute', 'ui.bootstrap', 'user-session', 'pascalprecht.translate']);

    TENSIDE.run(['$tensideApi', '$rootScope', function($tensideApi, $rootScope) {
        $tensideApi.setBaseUrl(TENSIDEApi);
        $rootScope.expertsMode = false;
    }])
    .factory('loadingHandler', ['$q', '$rootScope', function($q, $rootScope) {
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
    }])
    .config(
        ['$routeProvider', '$locationProvider', '$httpProvider', '$translateProvider', '$tooltipProvider',
        function ($routeProvider, $locationProvider, $httpProvider, $translateProvider, $tooltipProvider) {

        $locationProvider.html5Mode(false);

        var translations = {
            SLOGAN: 'Package management made easy!',
            LOADING: 'Loading...'
        };

        $translateProvider
            .translations('en', translations)
            .usePostCompiling(true)
            .preferredLanguage('en');

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

        $httpProvider.interceptors.push('loadingHandler');

        $tooltipProvider.options({
            placement: 'top',
            appendToBody: true
        });
    }])
    .controller('tensideAboutController', ['$scope', function ($scope) {
        $scope.config = {};
    }])
    .controller('tensideSupportController', ['$scope', function ($scope) {
        $scope.config = {};
    }]);
})();
