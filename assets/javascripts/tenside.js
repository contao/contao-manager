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

(function () {
    var app = angular.module('tenside', ['ngRoute']);

    TENSIDE = app;

    TENSIDE.config(function ($routeProvider, $locationProvider) {
        $locationProvider.html5Mode(false);

        // route for the home page
        $routeProvider.when('/', {
            templateUrl: 'pages/home.html',
            controller: 'tensideMainController'
        });

        // route to the packages
        $routeProvider.when('/packages', {
            templateUrl: 'pages/packages.html',
            controller: 'tensidePackages'
        });

        // route for the editor page
        $routeProvider.when('/editor', {
            templateUrl: 'pages/editor.html',
            controller: 'tensideEditor'
        });

        // route for config
        $routeProvider.when('/config', {
            templateUrl: 'pages/config.html',
            controller: 'tensideConfigController'
        });

        $routeProvider.otherwise({redirectTo: '/'});
    });

    app.controller('tensideMainController', ['$window', '$scope', '$location', function ($window, $scope, $location) {
        $scope.main = main;
        $scope.activePath = null;
        $scope.$on('$routeChangeSuccess', function () {
            $scope.activePath = $location.path();
            console.log($location.path());
        });
    }]);

    app.controller('tensidePackages', ['$window', '$scope', function ($window, $scope) {
        $scope.packages = {};
    }]);

    app.controller('tensideEditor', ['$window', '$scope', function ($window, $scope) {
        // var editor = ace.edit("editor");
        // editor.setTheme("ace/theme/monokai");
        // editor.getSession().setMode("ace/mode/javascript");
    }]);

    app.controller('tensideConfigController', ['$window', '$scope', function ($window, $scope) {
        $scope.config = {};
    }]);
})();
