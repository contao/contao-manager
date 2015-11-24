/**
 * This file is part of tenside/contao-ui.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/contao-ui
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/contao-ui/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/contao-ui
 * @filesource
 */

(function () {
    "use strict";
    angular
        .module('tenside-editor', ['tenside-api'])
        .config(
            [
                '$stateProvider',
                function ($stateProvider) {
                    $stateProvider
                        .state(
                            'AppKernel',
                            {
                                url: '/AppKernel.php',
                                templateUrl: 'pages/editor.html',
                                resolve: {
                                    '$stateParams': '$stateParams',
                                    '$tensideApi': '$tensideApi'
                                },
                                controller: function ($scope, $stateParams, $tensideApi) {
                                    $scope.type = 'php';
                                    $scope.content = '';
                                    $scope.errors = [];
                                    $scope.warnings = [];
                                    $scope.loadFile = $tensideApi.appKernelPhp.get;
                                    $scope.pushFile = $tensideApi.appKernelPhp.put;
                                }
                            }
                        )
                        .state(
                            'composer_json',
                            {
                                url: '/composer.json',
                                templateUrl: 'pages/editor.html',
                                resolve: {
                                    '$stateParams': '$stateParams',
                                    '$tensideApi': '$tensideApi'
                                },
                                controller: function ($scope, $stateParams, $tensideApi) {
                                    $scope.type = 'json';
                                    $scope.content = '';
                                    $scope.errors = [];
                                    $scope.warnings = [];
                                    $scope.loadFile = $tensideApi.composerJson.get;
                                    $scope.pushFile = $tensideApi.composerJson.put;
                                }
                            }
                        );
                }
            ]
        )
        .directive('tensideEditor',
            [
                '$timeout',
                function ($timeout) {
                    return {
                        restrict: 'A',
                        scope: {
                            'type': '@',
                            'loadFile': '&load',
                            'pushFile': '&push',
                            'content': '=',
                            'errors': '=',
                            'warnings': '='
                        },
                        link: function (scope, element) {
                            element = element[0];
                            var editor = ace.edit(element);

                            editor.getSession().setMode("ace/mode/" + scope.type);
                            editor.getSession().setTabSize(2);
                            editor.getSession().setUseSoftTabs(true);
                            // FIXME: this should be calculated properly.
                            editor.setOptions({maxLines: 35});

                            /**
                             * @see http://stackoverflow.com/a/13579233
                             */
                            var heightUpdateFunction = function () {
                                // http://stackoverflow.com/questions/11584061/
                                element.style.height =
                                    editor.getSession().getScreenLength() * editor.renderer.lineHeight
                                    + 2 * editor.renderer.scrollBarH.getHeight();

                                // This call is required for the editor to fix all of
                                // its inner structure for adapting to a change in size
                                editor.resize();
                            };

                            // Set initial size to match initial content
                            heightUpdateFunction();

                            // Whenever a change happens inside the ACE editor, update
                            // the size again
                            editor.getSession().on('change', heightUpdateFunction);

                            var timer;

                            scope.$on('$destroy', function () {
                                $timeout.cancel(timer);
                            });

                            var test = function () {
                                $timeout.cancel(timer);
                                timer = $timeout(function () {
                                    scope.pushFile({content: editor.getValue()}).success(function (data) {
                                        scope.errors = data.error;
                                        scope.warnings = data.warning;
                                    });
                                }, 2000);
                            };

                            scope.loadFile().success(function (data) {
                                editor.setValue(data);
                                editor.gotoLine(0);
                                editor.getSession().on('change', test);
                                test();
                            });
                        }
                    }
                }
            ]
        )
    ;

    // Late dependency injection
    TENSIDE.requires.push('tenside-editor');
})();
