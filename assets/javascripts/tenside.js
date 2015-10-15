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

    TENSIDE.run(
    ['$tensideApi', '$rootScope', '$window',
    function($tensideApi, $rootScope, $window) {
        $tensideApi.setBaseUrl(TENSIDEApi);
        $rootScope.expertsMode = ($window.sessionStorage.getItem('expertsMode') !== null);
        $rootScope.$watch('expertsMode', function (value, previous) {
            if (value !== previous) {
                if (value) {
                    $window.sessionStorage.setItem('expertsMode', 'yes');
                } else {
                    $window.sessionStorage.removeItem('expertsMode');
                }
            }
        });
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

        $translateProvider
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
    .controller('taskRunController',
    ['$scope', '$routeParams',
    function ($scope, $routeParams) {
        $scope.taskId = $routeParams.taskId;


    }])
    .controller('tensideAboutController', ['$scope', function ($scope) {
        $scope.config = {};
    }])
    .controller('tensideSupportController', ['$scope', function ($scope) {
        $scope.config = {};
    }]);
})();
