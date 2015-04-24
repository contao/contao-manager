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

(function(){
    var TENSIDE_API = angular.module('tenside-api', []);

    TENSIDE_API.factory('$tensideApi', ['$q', function ($q) {
        var
            buildResponse = function (data) {
                var defered = $q.defer();
                defered.success = function(fn){
                    defered.promise.then(fn);
                    return defered;
                };
                defered.error = function(fn){
                    defered.promise.then(null, fn);//or defered.promise.catch(fn)
                    return defered;
                };
                //defered.resolve((typeof data === 'string') ? data : {data: data});
                defered.resolve(data);

                return defered;
            },
            apiUrl,
            apiKey,
            version,
            api = function(config) {
                throw 'This is just a mock!';
            },
            tensideApiPackages = function (tensideApi) {
                this.list = function(all) {
                    return buildResponse(
                        {
                            "doctrine\/annotations": {
                                "name": "doctrine\/annotations",
                                    "version": "v1.2.3",
                                    "constraint": "[\u003E= 1.2.0.0-dev \u003C 2.0.0.0-dev]",
                                    "type": "library",
                                    "locked": false,
                                    "upgrade_version": "v1.2.4",
                                    "description": "Docblock Annotations Parser"
                            },
                            "incenteev\/composer-parameter-handler": {
                                "name": "incenteev\/composer-parameter-handler",
                                    "version": "v2.1.0",
                                    "constraint": "[\u003E= 2.0.0.0-dev \u003C 3.0.0.0-dev]",
                                    "type": "library",
                                    "locked": false,
                                    "description": "Composer script handling your ignored parameter file"
                            },
                            "sensio\/distribution-bundle": {
                                "name": "sensio\/distribution-bundle",
                                    "version": "v3.0.20",
                                    "constraint": "[\u003E= 3.0.0.0-dev \u003C 4.0.0.0-dev]",
                                    "type": "symfony-bundle",
                                    "locked": false,
                                    "upgrade_version": "v3.0.21",
                                    "description": "Base bundle for Symfony Distributions"
                            },
                            "sensio\/framework-extra-bundle": {
                                "name": "sensio\/framework-extra-bundle",
                                    "version": "v3.0.7",
                                    "constraint": "[\u003E= 3.0.0.0-dev \u003C 4.0.0.0-dev]",
                                    "type": "symfony-bundle",
                                    "locked": false,
                                    "description": "This bundle provides a way to configure your controllers with annotations"
                            },
                            "symfony\/symfony": {
                                "name": "symfony\/symfony",
                                    "version": "v2.6.6",
                                    "constraint": "[\u003E= 2.6.0.0-dev \u003C 3.0.0.0-dev]",
                                    "type": "library",
                                    "locked": false,
                                    "description": "The Symfony PHP framework"
                            }
                        }
                    );
                };
                this.get = function(name) {
                    return buildResponse(
                        {
                            'unmocked': 'yet'
                        }
                    );
                };
                this.put = function(data) {
                    return buildResponse(
                        {
                            "name": "doctrine\/annotations",
                            "version": "v1.2.3",
                            "constraint": "[\u003E= 1.2.0.0-dev \u003C 2.0.0.0-dev]",
                            "type": "library",
                            "locked": true,
                            "upgrade_version": "v1.2.4",
                            "description": "Docblock Annotations Parser"
                        }
                    );
                };
                this.delete = function () {
                    return buildResponse(
                        {

                        }
                    );
                };
            },
            tensideApiComposerJson = function (tensideApi) {
                this.get = function() {
                    return buildResponse(
                        JSON.stringify(
                            {
                                "name": "private/project",
                                "type": "project",
                                "description": "This is just a test project retrieved via mock API.",
                                "license": "proprietary",
                                "require": {
                                    "php": ">=5.5.0",
                                    "doctrine/annotations": "~1.2",
                                    "incenteev/composer-parameter-handler": "~2.0",
                                    "sensio/distribution-bundle": "~3.0",
                                    "sensio/framework-extra-bundle": "~3.0",
                                    "symfony/symfony": "~2.6"
                                },
                                "config": {
                                    "component-dir": "assets",
                                    "preferred-install": "dist"
                                },
                                "scripts": {
                                    "post-install-cmd": [
                                        "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters",
                                        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::buildBootstrap",
                                        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::clearCache",
                                        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installAssets"
                                    ],
                                    "post-update-cmd": [
                                        "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters",
                                        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::buildBootstrap",
                                        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::clearCache",
                                        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installAssets"
                                    ]
                                },
                                "extra": {
                                    "incenteev-parameters": {
                                        "file": "app/config/parameters.yml"
                                    },
                                    "symfony-assets-install": "relative",
                                    "tenside": []
                                }
                            },
                            null,
                            4
                        )
                    );
                };
                this.put = function(data) {
                    return buildResponse({
                        'status': 'ERROR',
                        'error': [
                            'This is just a dummy error on the mocked composer.json.'
                        ],
                        'warning': [
                            'This is just a dummy warning on the mocked composer.json.'
                        ]
                    });
                };
            };

        api.setBaseUrl = function(url) {
            apiUrl = url;
        };

        api.getBaseUrl = function() {
            return apiUrl;
        };

        api.hasKey = function() {
            return !!apiKey;
        };

        api.setKey = function(key) {
            apiKey = key;

            return self;
        };

        api.setVersion = function(ver) {
            version = ver;

            return self;
        };

        api.getVersion = function() {
            return version;
        };

        api.login = function(username, password) {
            return buildResponse({
                    "status":"ok",
                    "token":"xxx.yyy.zzz",
                    "acl":[
                        "upgrade",
                        "manipulate-requirements",
                        "edit-composer-json"
                    ]
                }
            );
        };

        api.get = function(url, config) {
            return buildResponse(
                {

                }
            );
        };
        api.put = function(url, data, config) {
            return buildResponse(
                {

                }
            );
        };
        api.post = function(url, data, config) {
            return buildResponse(
                {

                }
            );
        };
        api.delete = function(url, config) {
            return buildResponse(
                {

                }
            );
        };

        // Create our specific endpoints now.
        api.packages = new tensideApiPackages(api);
        api.composerJson = new tensideApiComposerJson(api);

        return api;
    }]);
}());
