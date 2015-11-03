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
        .module(
            'tenside-console',
            [
                'tenside-api', 'tenside-tasklist'
            ]
        )
        .directive('tensideConsole',
            [
                '$timeout', '$tensideApi',
                function ($timeout, $tensideApi) {
                    return {
                        restrict: 'E',
                        scope: true,
                        templateUrl: 'pages/tenside-console.html',
                        link: function (scope, element) {
                            var
                                container = element,
                                blur = angular.element(document.querySelector('#blur')),
                                hide = function () {
                                    blur.addClass('blur-out').removeClass('blur-in');
                                    container.removeClass('visible');
                                },
                                show = function () {
                                    blur.addClass('blur-in').removeClass('blur-out');
                                    container.addClass('visible');
                                };

                            // Initialize the scope.
                            scope.consoleVisible = scope.consoleVisible || false;
                            scope.close = function () {
                                if (scope.task.getStatus() === 'FINISHED') {
                                    $tensideApi.tasks.delete(scope.task.getId());
                                }
                                scope.task = null;
                                hide();
                                // FIXME: We must redirect from install to success screen here.
                            };
                            scope.cancel = function () {
                                // FIXME: we need an api endpoint to send a HUP to the task.
                                scope.task = null;
                                hide();
                            };

                            scope.$on(
                                'console-watch-task',
                                function (event, newTask) {
                                    scope.task = newTask;
                                    if (!scope.task) {
                                        hide();
                                    } else {
                                        show();
                                    }
                                }
                            );

                            scope.$on(
                                'tenside.tasklist.updated',
                                function (event, data) {
                                    var task = data.list.getCurrentTask();
                                    if (task && (task.getId() !== scope.task)) {
                                        scope.$broadcast('console-watch-task', task);
                                    }
                                }
                            );

                            scope.$on(
                                'tenside.task.updated',
                                function (event, task) {
                                    if (scope.task !== task) {
                                        return;
                                    }

                                    // Close the popup automatically if console is not visible.
                                    if (!scope.consoleVisible && task.isOutputComplete()) {
                                        scope.close();
                                    }

                                    if (true) {
                                        var output = document.getElementById('console-output');
                                        // needs a delay as it is some milliseconds behind.
                                        $timeout(function () {
                                            output.scrollTop = output.scrollHeight;
                                        }, 10);
                                    }
                                }
                            );
                        }
                    };
                }
            ]
        );
})();
