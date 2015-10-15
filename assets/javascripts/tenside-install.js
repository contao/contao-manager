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

var TensideInstaller;
var TENSIDEApi = TENSIDEApi || '';

(function () {
    TensideInstaller = angular.module(
        'tenside-install',
        ['ngAnimate', 'ui.bootstrap', 'pascalprecht.translate']
    );

    TensideInstaller
        .config(
        ['$translateProvider',
            function ($translateProvider) {
                $translateProvider
                    .registerAvailableLanguageKeys(['en', 'de'], {
                        'en*': 'en',
                        'de*': 'de'
                    })
                    .useStaticFilesLoader({
                        prefix: 'l10n/',
                        suffix: '.json'
                    })
                    .usePostCompiling(true)
                    .fallbackLanguage('en')
                    .determinePreferredLanguage();
                if ('' === $translateProvider.use()) {
                    $translateProvider.preferredLanguage('en')
                }
            }
        ])
        .controller(
        'TensideInstallController',
        ['$scope', '$http',
            function ($scope, $http) {
                function makeDefaultToken(len)
                {
                    if (!len) {
                        len = 20;
                    }
                    var text = "";
                    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

                    for( var i=0; i < len; i++ )
                        text += possible.charAt(Math.floor(Math.random() * possible.length));

                    return text;
                }

                function stepToIndex(step) {
                    for (var i=0;i<wizard.steps.length;i++) {
                        if (step === wizard.steps[i]) {
                            return i;
                        }
                    }

                    return undefined;
                }

                $scope.wizard = this;
                // FIXME: do we rather want to provide a text input here?
                $scope.projects = {
                    'contao/standard-edition': [],
                    'symfony/framework-standard-edition': []
                };

                angular.forEach($scope.projects,
                    function (versions, name) {
                        var project = name;
                        $http.get(TENSIDEApi + '/api/v1/install/search-project/' + name + '.json').success(function(data) {
                            $scope.projects[project] = data.versions;
                        });
                    }
                );

                $scope.install = {
                    credentials: {
                        username: '',
                        password: '',
                        secret: makeDefaultToken()
                    },
                    project: {
                        name: 'contao/standard-edition',
                        version: ''
                    }
                };
                var wizard = this;
                // FIXME: input data validation is totally undone yet.
                wizard.steps = ['welcome', 'user-data', 'base-project', 'summary'];
                wizard.step = 0;

                wizard.isCurrentStep = function (step) {
                    return wizard.getCurrentStep() === step;
                };

                wizard.setCurrentStep = function (step) {
                    wizard.step = stepToIndex(step);
                };

                wizard.getCurrentStep = function () {
                    return wizard.steps[wizard.step];
                };

                wizard.isFirstStep = function () {
                    return wizard.step === 0;
                };

                wizard.isLastStep = function () {
                    return wizard.step === (wizard.steps.length - 1);
                };

                wizard.getNextLabel = function () {
                    return (wizard.isLastStep()) ? 'Install' : 'Next';
                };

                wizard.handlePrevious = function () {
                    wizard.step -= (wizard.isFirstStep()) ? 0 : 1;
                };

                wizard.handleNext = function () {
                    if (wizard.isLastStep()) {
                        console.log($scope);
                        alert('We will now create your project.');

                        $http.put(TENSIDEApi + '/api/v1/install/create-project.json', $scope.install).success(function(data) {
                            if (data.status === 'OK') {
                                window.location.href = window.location.href.replace(/install\.html/, '');

                                return;
                            }
                            alert(data.message.join("\n"));
                        });

                    } else {
                        wizard.step += 1;
                    }
                };
            }
        ]);
})();
