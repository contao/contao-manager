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
    var app = angular.module('tenside-editor', []);

    app.controller('tensideEditorController', ['$window', '$scope', '$http', function ($window, $scope, $http) {
        var testComposerJson = function() {
            $http.post(TENSIDEApi + 'composer.json', editor.getValue()).success(function(data, status, headers, config) {
                $scope.errors   = data.error;
                $scope.warnings = data.warning;
            });
        };

        $http.get(TENSIDEApi + 'composer.json', {'transformResponse': []}).success(function(data, status, headers, config) {
            editor.setValue(data);
            editor.gotoLine(0);
            editor.getSession().on('change', testComposerJson);
            testComposerJson();
        });
    }]);

    // Late dependency injection
    TENSIDE.requires.push('tenside-editor');

    TENSIDE.config(function ($routeProvider, USER_ROLES) {
        // route for the editor page
        $routeProvider.when('/editor', {
            templateUrl: 'pages/editor.html',
            controller: 'tensideEditorController',
            data: {
                authorizedRoles: [USER_ROLES.admin]
            }
        });
    });

})();
