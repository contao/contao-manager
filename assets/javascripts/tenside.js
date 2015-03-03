/**
 * This file is part of tenside/ui.
 *
 * (c) Tristan Lins <https://github.com/tristanlins>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/ui
 * @author     Tristan Lins <https://github.com/tristanlins>
 * @author     Tim Becker <https://github.com/tim-bec>
 * @copyright  Tristan Lins <https://github.com/tristanlins>
 * @link       https://github.com/tenside/ui
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @filesource
 */

var TENSIDE;
var TENSIDEApi = TENSIDEApi || '';

(function () {
    var app = angular.module('tenside', ['ngRoute', "ui.bootstrap"]);

    TENSIDE = app;

    TENSIDE.config(function ($routeProvider, $locationProvider) {
        $locationProvider.html5Mode(false);

        // route to the packages page
        $routeProvider.when('/packages', {
            templateUrl: 'pages/packages.html',
            controller: 'tensidePackagesController'
        });

        // route to the search page
        $routeProvider.when('/search', {
            templateUrl: 'pages/search.html',
            controller: 'tensideSearchController'
        });

        // route for the editor page
        $routeProvider.when('/editor', {
            templateUrl: 'pages/editor.html',
            controller: 'tensideEditorController'
        });

        // route for config page
        $routeProvider.when('/config', {
            templateUrl: 'pages/config.html',
            controller: 'tensideConfigController'
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

        $routeProvider.otherwise({redirectTo: '/packages'});
    });

    app.controller('tensidePackagesController', ['$window', '$scope', function ($window, $scope) {
        $scope.packages = {};
    }]);

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
