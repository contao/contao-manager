/**
 * This file is part of tenside/core.
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
    var app = angular.module('tenside-packages', ['tenside-api']);

    app.controller('tensidePackagesController',
        ['$scope', '$routeParams', '$tensideApi',
        function ($scope, $routeParams, $tensideApi) {
        $scope.packages = [];

        // Mapping of version descriptors to css classes.
        $scope.versionToClass = function(version) {
            if (version.indexOf('dev-') > -1) {
                return 'label-default';
            }
            if (version.indexOf('-alpha') > -1) {
                return 'label-danger';
            }
            if (version.indexOf('-beta') > -1) {
                return 'label-warning';
            }
            if (version.indexOf('-RC') > -1) {
                return 'label-info';
            }

            return 'label-success';
        };

        $scope.lock = function (pack) {
            console.log(pack);
        };

        $tensideApi.packages.list().success(function(data) {
            $scope.packages = data;
        });
    }]);

    // Late dependency injection
    TENSIDE.requires.push('tenside-packages');

    TENSIDE.config(function ($routeProvider, USER_ROLES) {
        // route to the packages page
        $routeProvider.when('/packages', {
            templateUrl: 'pages/packages.html',
            controller: 'tensidePackagesController',
            data: {
                authorizedRoles: [USER_ROLES.admin]
            }
        });
    });

})();
