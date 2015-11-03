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
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/ui/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/ui
 * @filesource
 */

(function () {
    "use strict";
    angular
        .module('tenside-search', ['tenside-api', 'tenside-package-list-entry'])
        .config(
            [
                '$stateProvider',
                function ($stateProvider) {
                    $stateProvider.state(
                        'search',
                        {
                            url: '/search?keywords&type',
                            templateUrl: 'pages/search.html',
                            controller: 'tensideSearchController'
                        }
                    );
                }
            ]
        )
        .directive(
            'searchHeader',
            [
                '$state', '$stateParams',
                function ($state, $stateParams) {
                    return {
                        restrict: 'E',
                        scope: true,
                        templateUrl: 'pages/search-head.html',
                        link: function (scope, element, attrs) {
                            scope.keywords = $stateParams.keywords ? $stateParams.keywords : '';
                            scope.search = function () {
                                if (scope.keywords != '') {
                                    var params = { keywords: scope.keywords};
                                    if ($stateParams.type) {
                                        params.type = $stateParams.type;
                                    }
                                    $state.go('search', params, {reload: true});
                                }
                            }
                        }
                    }
                }
            ]
        )
        .controller(
            'tensideSearchController',
            [
                '$scope', '$tensideApi', '$stateParams', '$state',
                function ($scope, $tensideApi, $stateParams, $state) {
                    $scope.packages = {};
                    $scope.type = $stateParams.type ? $stateParams.type : 'all';

                    $tensideApi.search.search($stateParams.keywords, $scope.type)
                        .success(function (data) {
                            $scope.packages = data;
                        })
                        .error(function() {
                            // FIXME: handle search error here.
                        });

                    $scope.$watch('type', function (newValue, oldValue) {
                        if (oldValue != newValue) {
                            var params = { keywords: $stateParams.keywords };
                            if ($scope.type) {
                                params.type = $scope.type;
                            }
                            $state.go('search', params, {reload: true});
                        }
                    });
                }
            ]
        );

    // Late dependency injection
    TENSIDE.requires.push('tenside-search');
})();
