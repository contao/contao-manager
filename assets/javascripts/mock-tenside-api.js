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
        var MOCKDATA = {
            packages: {
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
            },
            composerJson: {
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
            searchResult: {
                "contao-components\/installer": {
                    "name": "contao-components\/installer",
                    "description": "Contao components installer",
                    "installed": "1.0.4",
                    "type": "composer-installer"
                },
                "contao-components\/compass": {
                    "name": "contao-components\/compass",
                    "description": "Compass integration for Contao Open Source CMS",
                    "installed": "0.12.2",
                    "type": "library"
                },
                "psr\/log": {
                    "name": "psr\/log",
                    "description": "Common interface for logging libraries",
                    "installed": "1.0.0",
                    "type": "library"
                },
                "doctrine\/inflector": {
                    "name": "doctrine\/inflector",
                    "description": "Common String Manipulations with regard to casing and singular\/plural rules.",
                    "installed": "v1.0.1",
                    "type": "library"
                },
                "doctrine\/collections": {
                    "name": "doctrine\/collections",
                    "description": "Collections Abstraction library",
                    "installed": "v1.3.0",
                    "type": "library"
                },
                "doctrine\/common": {
                    "name": "doctrine\/common",
                    "description": "Common Library for Doctrine projects",
                    "installed": "v2.5.0",
                    "type": "library"
                },
                "true\/punycode": {
                    "name": "true\/punycode",
                    "description": "A Bootstring encoding of Unicode for Internationalized Domain Names in Applications (IDNA)",
                    "installed": "1.1.0",
                    "type": "library"
                },
                "tecnick.com\/tcpdf": {
                    "name": "tecnick.com\/tcpdf",
                    "description": "TCPDF is a PHP class for generating PDF documents and barcodes.",
                    "installed": "6.2.8",
                    "type": "library"
                },
                "phpspec\/php-diff": {
                    "name": "phpspec\/php-diff",
                    "description": "A comprehensive library for generating differences between two hashable objects (strings or arrays).",
                    "installed": "v1.0.2",
                    "type": "library"
                },
                "leafo\/scssphp": {
                    "name": "leafo\/scssphp",
                    "description": "scssphp is a compiler for SCSS written in PHP.",
                    "installed": "v0.1.1",
                    "type": "library"
                },
                "contao\/core-bundle": {
                    "name": "contao\/core-bundle",
                    "description": "Contao 4 core bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/newsletter-bundle": {
                    "name": "contao\/newsletter-bundle",
                    "description": "Contao 4 newsletter bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/news-bundle": {
                    "name": "contao\/news-bundle",
                    "description": "Contao 4 news bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/listing-bundle": {
                    "name": "contao\/listing-bundle",
                    "description": "Contao 4 listing bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/faq-bundle": {
                    "name": "contao\/faq-bundle",
                    "description": "Contao 4 FAQ bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/comments-bundle": {
                    "name": "contao\/comments-bundle",
                    "description": "Contao 4 comments bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/calendar-bundle": {
                    "name": "contao\/calendar-bundle",
                    "description": "Contao 4 calendar bundle",
                    "installed": "4.0.0-beta1",
                    "type": "library"
                },
                "contao\/contao": {
                    "name": "contao\/contao",
                    "description": "Contao Open Source CMS",
                    "installed": "4.0.0-beta1",
                    "type": "metapackage"
                },
                "incenteev\/composer-parameter-handler": {
                    "name": "incenteev\/composer-parameter-handler",
                    "description": "Composer script handling your ignored parameter file",
                    "installed": "v2.1.0",
                    "type": "library"
                },
                "sensiolabs\/security-checker": {
                    "name": "sensiolabs\/security-checker",
                    "description": "A security checker for your composer.lock",
                    "installed": "v2.0.2",
                    "type": "library"
                },
                "sensio\/distribution-bundle": {
                    "name": "sensio\/distribution-bundle",
                    "description": "Base bundle for Symfony Distributions",
                    "installed": "v3.0.22",
                    "type": "symfony-bundle"
                },
                "sensio\/framework-extra-bundle": {
                    "name": "sensio\/framework-extra-bundle",
                    "description": "This bundle provides a way to configure your controllers with annotations",
                    "installed": "v3.0.7",
                    "type": "symfony-bundle"
                },
                "composer-plugin-api": {
                    "name": "composer-plugin-api",
                    "description": "The Composer Plugin API",
                    "installed": null,
                    "type": "library"
                },
                "ext-iconv": {
                    "name": "ext-iconv",
                    "description": "The iconv PHP extension",
                    "installed": null,
                    "type": "library"
                },
                "lib-iconv": {
                    "name": "lib-iconv",
                    "description": "The iconv PHP library",
                    "installed": null,
                    "type": "library"
                },
                "openeyes\/oph-co-correspondence": {
                    "name": "openeyes\/oph-co-correspondence",
                    "description": "Correspondence module for OpenEyes, allows generation of letters",
                    "url": "https:\/\/packagist.org\/packages\/openeyes\/oph-co-correspondence",
                    "repository": "https:\/\/github.com\/openeyes\/OphCoCorrespondence",
                    "downloads": 266,
                    "favers": 0,
                    "installed": null,
                    "type": "openeyes-module"
                },
                "openeyes\/oph-co-therapyapplication": {
                    "name": "openeyes\/oph-co-therapyapplication",
                    "description": "Generates applications for therapy for patients within OpenEyes",
                    "url": "https:\/\/packagist.org\/packages\/openeyes\/oph-co-therapyapplication",
                    "repository": "https:\/\/github.com\/openeyes\/OphCoTherapyapplication",
                    "downloads": 226,
                    "favers": 0,
                    "installed": null,
                    "type": "openeyes-module"
                },
                "cos800\/tmdphp": {
                    "name": "cos800\/tmdphp",
                    "description": "tmd php framework",
                    "url": "https:\/\/packagist.org\/packages\/cos800\/tmdphp",
                    "repository": "https:\/\/github.com\/cos800\/tmdphp",
                    "downloads": 22,
                    "favers": 0,
                    "installed": null,
                    "type": "framework"
                },
                "symfony\/console": {
                    "name": "symfony\/console",
                    "description": "The Symfony PHP framework",
                    "url": "https:\/\/packagist.org\/packages\/symfony\/console",
                    "repository": "https:\/\/github.com\/symfony\/Console",
                    "downloads": 9376594,
                    "favers": 37,
                    "installed": null,
                    "type": "library"
                },
                "phpunit\/php-code-coverage": {
                    "name": "phpunit\/php-code-coverage",
                    "description": "Library that provides collection, processing, and rendering functionality for PHP code coverage information.",
                    "url": "https:\/\/packagist.org\/packages\/phpunit\/php-code-coverage",
                    "repository": "git:\/\/github.com\/sebastianbergmann\/php-code-coverage.git",
                    "downloads": 6752262,
                    "favers": 12,
                    "installed": null,
                    "type": "library"
                },
                "sebastian\/comparator": {
                    "name": "sebastian\/comparator",
                    "description": "Provides the functionality to compare PHP values for equality",
                    "url": "https:\/\/packagist.org\/packages\/sebastian\/comparator",
                    "repository": "git@github.com:sebastianbergmann\/comparator.git",
                    "downloads": 3226415,
                    "favers": 1,
                    "installed": null,
                    "type": "library"
                },
                "doctrine\/instantiator": {
                    "name": "doctrine\/instantiator",
                    "description": "A small, lightweight utility to instantiate objects in PHP without invoking their constructors",
                    "url": "https:\/\/packagist.org\/packages\/doctrine\/instantiator",
                    "repository": "https:\/\/github.com\/doctrine\/instantiator",
                    "downloads": 2649295,
                    "favers": 2,
                    "installed": null,
                    "type": "library"
                },
                "sebastian\/recursion-context": {
                    "name": "sebastian\/recursion-context",
                    "description": "Provides functionality to recursively process PHP variables",
                    "url": "https:\/\/packagist.org\/packages\/sebastian\/recursion-context",
                    "repository": "https:\/\/github.com\/sebastianbergmann\/recursion-context",
                    "downloads": 1551928,
                    "favers": 0,
                    "installed": null,
                    "type": "library"
                },
                "symfony\/security-core": {
                    "name": "symfony\/security-core",
                    "description": "The Symfony PHP framework",
                    "url": "https:\/\/packagist.org\/packages\/symfony\/security-core",
                    "repository": "https:\/\/github.com\/symfony\/security-core",
                    "downloads": 3417288,
                    "favers": 2,
                    "installed": null,
                    "type": "library"
                },
                "ircmaxell\/password-compat": {
                    "name": "ircmaxell\/password-compat",
                    "description": "A compatibility library for the proposed simplified password hashing algorithm: https:\/\/wiki.php.net\/rfc\/password_hash",
                    "url": "https:\/\/packagist.org\/packages\/ircmaxell\/password-compat",
                    "repository": "https:\/\/github.com\/ircmaxell\/password_compat.git",
                    "downloads": 4409429,
                    "favers": 23,
                    "installed": null,
                    "type": "library"
                },
                "symfony\/config": {
                    "name": "symfony\/config",
                    "description": "The Symfony PHP framework",
                    "url": "https:\/\/packagist.org\/packages\/symfony\/config",
                    "repository": "https:\/\/github.com\/symfony\/Config",
                    "downloads": 3210498,
                    "favers": 5,
                    "installed": null,
                    "type": "library"
                },
                "kriswallsmith\/assetic": {
                    "name": "kriswallsmith\/assetic",
                    "description": "Asset Management for PHP",
                    "url": "https:\/\/packagist.org\/packages\/kriswallsmith\/assetic",
                    "repository": "http:\/\/github.com\/kriswallsmith\/assetic.git",
                    "downloads": 5783979,
                    "favers": 36,
                    "installed": null,
                    "type": "library"
                }
            }
        };

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
                        MOCKDATA.packages
                    );
                };
                this.get = function(name) {
                    return buildResponse(
                        MOCKDATA.packages[name]
                    );
                };
                this.put = function(data) {
                    MOCKDATA.packages[data.name].locked = data.locked;
                    MOCKDATA.packages[data.name].constraint = data.constraint;
                    return buildResponse(MOCKDATA.packages[data.name]);
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
                            MOCKDATA.composerJson,
                            null,
                            4
                        )
                    );
                };
                this.put = function(data) {
                    MOCKDATA.composerJson = data;
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
            },
            tensideApiSearch = function (tensideApi) {
                this.search = function(keywords) {
                    return buildResponse(MOCKDATA.searchResult);
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
        api.search = new tensideApiSearch(api);

        return api;
    }]);
}());
