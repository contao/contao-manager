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

(function() {
    var app = angular.module('tenside', ['ngRoute']);

    TENSIDE = app;

    TENSIDE.config(function($routeProvider, $locationProvider) {
    	$routeProvider.
	    	// route for the home page
	    	when('/', {
	    		templateUrl : 'pages/home.html',
	    		controller  : 'tensideMainController'}).

	    	// route to the packages
	    	when('/packages', {
	    		templateUrl : 'pages/packages.html',
	    		controller : 'tensidePackages'}).

	    	// route for the editor page
	    	when('/editor', {
	    		templateUrl : 'pages/composer-generator.html',
	    		controller : 'tensideComposerGenerator'}).

	    	// route for config
	    	when('/config', {
	    		templateUrl : 'pages/config.html',
	    		controller : 'tensideConfigController'}).

	    	otherwise({redirectTo: '/'});
	    	$locationProvider.html5Mode( true );
    });

    app.controller('tensideMainController', ['$window', '$scope', '$location',  function($window, $scope) {
        $scope.main =  main;
        $scope.activePath = null;
		$scope.$on('$routeChangeSuccess', function(){
			$scope.activePath = $location.path();
			console.log( $location.path() );
		});
    }]);

    app.controller('tensidePackages', ['$window', '$scope', function($window, $scope) {
        $scope.packages =  packages;
    }]);

    app.controller('tensideComposerGenerator', ['$window', '$scope', function($window, $scope) {
        $scope.generator =  generator;
    }]);

    app.controller('tensideConfigController', ['$window', '$scope', function($window, $scope) {
        $scope.config =  config;
    }]);
})();
