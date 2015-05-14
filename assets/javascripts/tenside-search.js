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
        $scope.typeImage = function(typeName) {
            switch (typeName) {
                case 'component':
                case 'composer-installer':
                case 'composer-plugin':
                case 'legacy-contao-module':
                case 'meta-package':
                case 'metapackage':
                case 'php':
                    return 'img/type-' + typeName + '.png';
                case 'symfony-bundle':
                    return 'img/type-symfony-bundle.svg';
                default:
            }

            return 'img/type-library.png';
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
