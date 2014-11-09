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
 * @copyright  Tristan Lins <https://github.com/tristanlins>
 * @link       https://github.com/tenside/ui
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @filesource
 */

(function() {
    var app = angular.module('tenside-search', []);

    var search = { term: '' };

    app.directive('tensideSearch', function() {
        return {
            restrict: 'E',
            templateUrl: 'tenside/search-navbar.html'
        };
    });

    app.controller('TensideSearchController', ['$window', '$scope', function($window, $scope) {
        $scope.search = search;
    }]);

    // Late dependency injection
    TENSIDE.requires.push('tenside-search');
})();
