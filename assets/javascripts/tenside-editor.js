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
        .module('tenside-editor', ['tenside-api'])
        .config(
            [
                '$stateProvider',
                function ($stateProvider) {
                    $stateProvider.state(
                        'editor',
                        {
                            url: '/editor',
                            templateUrl: 'pages/editor.html',
                            controller: 'tensideEditorController'
                        }
                    );
                }
            ]
        )
        .controller('tensideEditorController',
            ['$scope', '$timeout', '$tensideApi',
                function ($scope, $timeout, $tensideApi) {
                    var editor;
                    if (editor === undefined) {
                        editor = (function (elementId) {
                            var editor = ace.edit(elementId);

                            editor.getSession().setMode("ace/mode/json");
                            editor.getSession().setTabSize(2);
                            editor.getSession().setUseSoftTabs(true);
                            // FIXME: this should be calculated properly.
                            editor.setOptions({maxLines: 35});

                            /**
                             * @see http://stackoverflow.com/a/13579233
                             */
                            var heightUpdateFunction = function () {
                                // http://stackoverflow.com/questions/11584061/
                                var newHeight =
                                    editor.getSession().getScreenLength() * editor.renderer.lineHeight
                                    + 2 * editor.renderer.scrollBarH.getHeight();

                                $('#editor').css('height', newHeight);

                                // This call is required for the editor to fix all of
                                // its inner structure for adapting to a change in size
                                editor.resize();
                            };

                            // Set initial size to match initial content
                            heightUpdateFunction();

                            // Whenever a change happens inside the ACE editor, update
                            // the size again
                            editor.getSession().on('change', heightUpdateFunction);

                            return editor;
                        })('editor');
                    }
                    var timer;

                    $scope.$on('$destroy', function () {
                        $timeout.cancel(timer);
                    });

                    var testComposerJson = function () {
                        $timeout.cancel(timer);
                        timer = $timeout(function () {
                            $tensideApi.composerJson.put(editor.getValue()).success(function (data) {
                                $scope.errors = data.error;
                                $scope.warnings = data.warning;
                            });
                        }, 2000);
                    };

                    $tensideApi.composerJson.get().success(function (data) {
                        editor.setValue(data);
                        editor.gotoLine(0);
                        editor.getSession().on('change', testComposerJson);
                        testComposerJson();
                    });
                }
            ]
        )
    ;

    // Late dependency injection
    TENSIDE.requires.push('tenside-editor');
})();
