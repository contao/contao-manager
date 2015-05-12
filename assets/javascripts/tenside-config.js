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

(function () {
    angular.module('tenside-config', ['tenside-api'])
        .config(
        ['$routeProvider', '$translateProvider',
            function ($routeProvider, $translateProvider) {
                var translations = {
                    CONFIG: {
                        HEADLINE: 'Configuration',
                        SAVE: 'Save',
                        CORE: {
                            TITLE: 'General'
                        },
                        INSTALL: {
                            TITLE: 'Installation',
                            MINIMUM_STABILITY: {
                                STABLE: 'Stable releases (recommended)',
                                RC: 'Release candidates',
                                BETA: 'Beta releases',
                                ALPHA: 'Alpha releases',
                                DEV: 'Bleeding edge development versions'
                            },
                            PREFERRED_INSTALL: {
                                DIST: 'Distribution archive (recommended)',
                                SOURCE: 'From source (git, mercurial or svn)',
                                AUTO: 'Guess automatically'
                            }
                        },
                        DOWNLOAD: {
                            TITLE: 'Download',
                            REPOSITORY: {
                                TYPE: 'Type',
                                URL: 'URL',
                                ALLOW_SSL_DOWNGRADE: 'Allow SSL downgrade',
                                // Buttons
                                DELETE: 'Remove repository',
                                ADD: 'Add repository',
                                // Types:
                                TYPES: {
                                    COMPOSER: 'Composer (like packagist.org)',
                                    ARTIFACT: 'Artifact repository',
                                    VCS: 'Source repository'
                                }
                            }
                        }
                    }
                };

                $translateProvider.translations('en', translations);

                // route for config page
                $routeProvider.when('/config', {
                    templateUrl: 'pages/config.html',
                    controller: 'tensideConfigController'
                });
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

                $scope.removeRepository = function(key) {
                    delete $scope.values.repositories[key];
                };

                $scope.addRepository = function() {
                    $scope.values.repositories.push({});
                }
            }
        ]);

    // Late dependency injection
    TENSIDE.requires.push('tenside-config');
})();
