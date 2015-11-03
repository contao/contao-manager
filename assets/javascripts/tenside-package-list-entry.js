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
        .module('tenside-package-list-entry', ['ui.bootstrap'])
        .directive(
            'packageType',
            ['$translate',
                function ($translate) {
                    return function (scope, element, attrs) {
                        var
                            typeName = scope.$eval(attrs.packageType),
                            matched = (function (typeName) {
                                switch (typeName) {
                                    case 'library':
                                        return 'fa-puzzle-piece';
                                    case 'component':
                                        return 'fa-cog';
                                    case 'composer-installer':
                                        return 'fa-magic';
                                    case 'composer-plugin':
                                        return 'fa-plug';
                                    case 'legacy-contao-module':
                                        return 'fa-thumbs-down';
                                    case 'contao-bundle':
                                    case 'contao-module':
                                        return 'fa-contao';
                                    case 'meta-package':
                                    case 'metapackage':
                                        return 'fa-cubes';
                                    case 'php':
                                        return 'fa-code-o';
                                    case 'symfony-bundle':
                                        return 'fa-archive';
                                    default:
                                }

                                return 'fa-question';
                            })(typeName);

                        element.addClass(matched);

                        $translate('tenside_package_list_entry.package_type.' + typeName).then(function (translation) {
                            element[0].title = translation;
                        }, function () {
                            element[0].title = typeName;
                        });
                    }
                }
            ]
        )
        .directive(
            'versionClass',
            function () {
                return function (scope, element, attrs) {
                    element.addClass((function (version) {
                        if (!version) {
                            return '';
                        }
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
                    })(scope.$eval(attrs.versionClass)));
                }
            }
        )
        .directive(
            'packageListEntry',
            [
                '$state', '$tensideApi',
                function ($state, $tensideApi) {
                    return {
                        restrict: 'E',
                        scope: true,
                        templateUrl: 'pages/package-list-entry.html',
                        link: function (scope, element, attrs) {
                            scope.canInstall = function (pack) {
                                return !pack.installed;
                            };
                            scope.canUpgrade = function (pack) {
                                return !pack.locked && pack.upgrade_version;
                            };

                            scope.canRemove = function (pack) {
                                return !pack.locked && pack.installed && pack.constraint;
                            };

                            scope.canEnable = function (pack) {
                                return pack.installed;
                            };

                            scope.canLock = function (pack) {
                                return !pack.locked && pack.installed;
                            };

                            scope.canUnlock = function (pack) {
                                return pack.locked && pack.installed;
                            };

                            scope.enable = scope.disable = function () {
                                $state.go('edit', {file: 'AppKernel'});
                            };

                            scope.lock = function (pack) {
                                var newPack = angular.merge({}, pack, {locked: true});
                                $tensideApi.packages.put(newPack).success(updatePackage);
                            };

                            scope.unlock = function (pack) {
                                var newPack = angular.merge({}, pack, {locked: false});
                                $tensideApi.packages.put(newPack).success(updatePackage);
                            };

                            scope.install = function(pack) {
                                $tensideApi.tasks.addRequire(pack.name);
                            };

                            scope.remove = function (pack) {
                                $tensideApi.tasks.addRemove(pack.name);
                            };

                            /*
                             scope.upgrade = function (pack) {
                             // pack is optional.
                             console.log(pack);
                             $tensideApi.tasks.addUpgrade(pack ? [pack.name] : undefined);
                             };

                             scope.remove = function (pack) {
                             $tensideApi.packages.delete(pack).success(function () {
                             reload();
                             })
                             };
                             */

                            var updatePackage = function (data) {
                                scope.packages[data.name] = data;
                            };
                        }
                    };
                }
            ]
        )
    ;

    // Late dependency injection
    TENSIDE.requires.push('tenside-package-list-entry');
})();
