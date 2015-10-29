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
    angular.module('tenside-config', ['tenside-api'])
        .config(
            [
                '$stateProvider',
                function ($stateProvider) {
                    $stateProvider.state(
                        'config',
                        {
                            url: '/config',
                            templateUrl: 'pages/config.html',
                            controller: 'tensideConfigController'
                        }
                    );
                }])
        .controller(
            'tensideConfigController',
            ['$scope',
                function ($scope) {
                    // FIXME: make an API call for this.
                    $scope.values = {
                        'upgrade_mode': 'inline',
                        'github_token': 'abc1234',
                        'preferred_install': 'auto',
                        'minimum_stability': 'dev',
                        'repositories': [
                            {
                                'type': 'composer',
                                'url': 'https?://legacy-packages-via.contao-community-alliance.org',
                                'allow_ssl_downgrade': true
                            },
                            {
                                'type': 'composer',
                                'url': 'https?://packagist.org',
                                'allow_ssl_downgrade': true
                            },
                            {
                                "type": "vcs",
                                "url": "git@git.cyberspectrum.de:tenside/core.git"
                            },
                            {
                                "type": "vcs",
                                "url": "git@git.cyberspectrum.de:tenside/ui.git"
                            }
                        ]
                    };

                    $scope.removeRepository = function (key) {
                        delete $scope.values.repositories[key];
                    };

                    $scope.addRepository = function () {
                        $scope.values.repositories.push({});
                    }
                }
            ]);

    // Late dependency injection
    TENSIDE.requires.push('tenside-config');
})();
