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

(function() {
    angular.module('tenside-search', ['tenside-api'])
    .factory('tensideSearchData', function() {
        var data = {
            keyword: ''
        };

        return {
            setKeywords: function (keywords) {
                data.keyword = keywords;
            },
            getKeywords: function() {
                return data.keyword;
            }
        };
    })
    .controller('tensideSearchHeader',
    ['$scope', 'tensideSearchData', '$location',
    function ($scope, data, $location) {
        $scope.keywords = data.getKeywords();

        $scope.search = function() {
            if($scope.keywords != '') {
                data.setKeywords($scope.keywords);
            }
        };

        $scope.searchButtonActive = $scope.keywords == '' ? '' : 'disabled';
    }])
    .controller('tensideSearchController',
    ['$scope', '$tensideApi', 'tensideSearchData',
    function($scope, $tensideApi, data) {
        var search = function (keywords) {
            $tensideApi.search.search(keywords).success(function(data) {
                $scope.packages = data;
            });
        };

        $scope.$watch(
            function() {
                return data.getKeywords();
            },
            function (value, previous) {
                if (value !== previous) {
                    search(value);
                }
            }
        );
        $scope.packages = {
        };
        // FIXME: make this some library and rip this method from package and search controller
        $scope.typeIcon = function(typeName) {
            switch (typeName) {
                case 'library':
                    return 'fa-puzzle-piece';
                case 'component':
                    return 'fa-cog';
                case 'composer-installer':
                    return 'fa-magic';
                case 'composer-plugin':
                    return 'fa-plug';
                case 'legacy-contao-module':
                    return 'fa-thumbs-down';
                case 'meta-package':
                    return 'fa-cubes';
                case 'metapackage':
                    return 'fa-cubes';
                case 'php':
                    return 'fa-code-o';
                case 'symfony-bundle':
                    return 'fa-archive';
                default:
            }

            return 'fa-question';
        };
    }])
    .config(['$routeProvider', function ($routeProvider) {
        // route to the search page
        $routeProvider.when('/search', {
            templateUrl: 'pages/search.html',
            controller: 'tensideSearchController'
        });
    }]);

    // Late dependency injection
    TENSIDE.requires.push('tenside-search');
})();
