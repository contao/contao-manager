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
        .module('tenside-packages', ['tenside-api', 'tenside-package-list-entry'])
        .config(
            [
                '$stateProvider',
                function ($stateProvider) {
                    $stateProvider.state(
                        'packages',
                        {
                            url: '/packages',
                            templateUrl: 'pages/packages.html',
                            controller: 'tensidePackagesController'
                        }
                    );
                }
            ]
        )
        .controller(
            'tensidePackagesController',
            ['$scope', '$tensideApi',
                function ($scope, $tensideApi) {
                    $scope.packages = {};

                    $tensideApi.packages.list($scope.showDependencies).success(function (data) {
                        $scope.packages = data;
                    });
                }
            ]
        );

    // Late dependency injection
    TENSIDE.requires.push('tenside-packages');
})();
