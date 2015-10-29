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
    angular
        .module(
            'tenside-install',
            ['ui.router']
        )
        .config(
            [
                '$stateProvider',
                function ($stateProvider) {
                    $stateProvider
                        .state('install', {
                            url: '/install',
                            templateUrl: 'pages/install.html',
                            controller: 'TensideInstallController'
                        });
                }
            ])
        .controller(
            'TensideInstallController',
            ['$scope', '$http', '$rootScope', '$tensideApi', 'TensideTasks',
                function ($scope, $http, $rootScope, $tensideApi, TensideTasks) {
                    function makeDefaultToken(len) {
                        if (!len) {
                            len = 20;
                        }
                        var text = "";
                        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

                        for (var i = 0; i < len; i++)
                            text += possible.charAt(Math.floor(Math.random() * possible.length));

                        return text;
                    }

                    // FIXME: do we rather want to provide a type ahead text input here?
                    $scope.projects = {
                        'contao/standard-edition': [],
                        'symfony/framework-standard-edition': []
                    };

                    angular.forEach($scope.projects,
                        function (versions, name) {
                            var project = name;
                            $http
                                .get(TENSIDEApi + '/api/v1/install/search-project/' + name + '.json')
                                .success(function (data) {
                                    $scope.projects[project] = data.versions;
                                });
                        }
                    );

                    $scope.$watch('install.project', function (value, previous) {
                        if (value !== previous) {
                            $http
                                .get(TENSIDEApi + '/api/v1/install/search-project/' + name + '.json')
                                .success(function (data) {
                                    $scope.projects[project] = data.versions;
                                });
                        }
                    });

                    $scope.install = {
                        credentials: {
                            username: '',
                            password: '',
                            secret: makeDefaultToken()
                        },
                        project: {
                            name: '',
                            version: ''
                        }
                    };
                    $scope.password_confirm = '';

                    $scope.perform = function () {
                        if ($scope.install.credentials.password !== $scope.password_confirm) {
                            alert('Password does not match');
                            return;
                        }

                        $http
                            .put(TENSIDEApi + '/api/v1/install/create-project.json', $scope.install)
                            .success(function (data) {
                                if (data.status === 'OK') {
                                    $tensideApi.setKey(data.token);
                                    TensideTasks.startPolling();

                                    return;
                                }

                                alert(data.message.join("\n"));
                            });
                    };
                }
            ]
        );
})();
