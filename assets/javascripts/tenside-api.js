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

    TENSIDE_API.factory('$tensideApi',
    ['$http', 'Base64', '$window',
    function ($http, Base64, $window) {
        var
            http = $http,
            apiUrl,
            apiKey,
            version,
            api = function(config) {
                return http(prepareConfig(config));
            },
            endpoint = function(endpoint) {
                if (-1 < endpoint.indexOf(api.getBaseUrl())) {
                    return endpoint;
                }

                if (version === undefined) {
                    version = 'v1';
                }

                return api.getBaseUrl() + '/api/' + version + '/' + endpoint;
            },
            prepareConfig = function(options) {
                var myOpts = angular.merge({}, options || {}, {
                    url: endpoint(options.url),
                    headers: {}
                });
                if (!myOpts.headers.authorization && apiKey !== undefined) {
                    myOpts.headers.authorization = 'Bearer ' + apiKey;
                }
                return myOpts;
            },
            tensideApiPackages = function (tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function(name) {
                        if (name) {
                            return endpoint() + '/' + name;
                        }

                        return 'packages';
                    };
                self.list = function(all, solveDependencies) {
                    var data = {params: {}};
                    if (all) {
                        data.params['all'] = '';
                    }
                    if (solveDependencies) {
                        data.params['solve'] = '';
                    }
                    return api.get(endpoint(), data);
                };
                self.get = function(name) {
                    if (!name) {throw 'no name passed to $tensideApi.packages.get()';}

                    return api.get(endpoint(name));
                };
                self.put = function(data) {
                    if (!data.name) {throw 'no name passed to $tensideApi.packages.put()';}

                    return api.put(endpoint(data.name), data);
                };
                self.delete = function (data) {
                    return api.delete(endpoint(data.name));
                };
            },
            tensideApiComposerJson = function (tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function() {
                        return 'composer.json';
                    }
                    ;
                self.get = function() {
                    return api.get(endpoint(), {'transformResponse': []});
                };
                self.put = function(data) {
                    return api.put(endpoint(), data);
                };
            },
            tensideApiAppKernelPhp = function (tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function() {
                        return 'AppKernel.php';
                    }
                    ;
                self.get = function() {
                    return api.get(endpoint(), {'transformResponse': []});
                };
                self.put = function(data) {
                    return api.put(endpoint(), data);
                };
            },
            tensideApiSearch = function (tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function() {
                        return 'search';
                    }
                    ;
                self.search = function(keywords, type) {
                    if (!keywords) {throw 'no keywords passed to $tensideApi.search.search()';}
                    var data = {keywords: keywords};
                    if (type) {
                        data['type'] = type;
                    }
                    return api.put(endpoint(), data);
                };
            },
            tensideApiTasks = function(tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function(name) {
                        if (name) {
                            return endpoint() + '/' + name;
                        }

                        return 'tasks';
                    };
                self.list = function() {
                    return api.get(endpoint());
                };
                self.get = function(id, offset) {
                    if (!id) {throw 'no id passed to $tensideApi.tasks.get()';}

                    var config;

                    if (offset) {
                        config = {params: {offset: offset}};
                    }

                    return api.get(endpoint(id), config);
                };
                self.add = function(data) {
                    return api.post(endpoint(), data);
                };
                self.addUpgrade = function(packageNames) {
                    if (packageNames) {
                        return self.add({type: 'upgrade', packages: packageNames});
                    }
                    return self.add({type: 'upgrade'});
                };
                self.addRequire = function(packageName) {
                    return self.add({type: 'require-package', package: [packageName]});
                };
                self.addRemove = function(packageName) {
                    return self.add({type: 'remove-package', package: [packageName]});
                };
                self.delete = function (id) {
                    return api.delete(endpoint(id));
                };
                self.run = function () {
                    return api.get(endpoint('run'));
                };

                self.runInline = function(id) {
                    return api.get('run-task/'+ id);
                }
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

        api.setKey = function(key, store) {
            apiKey = key;

            switch (store) {
                case 'session':
                    $window.sessionStorage.apiKey = key;
                    break;
                case 'local':
                    $window.localStorage.apiKey = key;
                    break;
            }

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
            var authdata = Base64.encode(username + ':' + password);
            return http.get(endpoint('auth'), {headers: {authorization: 'Basic ' + authdata}})
        };

        api.get = function(url, config) {
            //return api(jQuery.extend({url: url, method: 'get'}, config));
            return api(angular.merge({url: url, method: 'get'}, config));
        };
        api.put = function(url, data, config) {
            return api(angular.merge({url: url, method: 'put', data: data}, config));
        };
        api.post = function(url, data, config) {
            return api(angular.merge({url: url, method: 'post', data: data}, config));
        };
        api.delete = function(url, config) {
            return api(angular.merge({url: url, method: 'delete'}, config));
        };

        // Create our specific endpoints now.
        api.packages = new tensideApiPackages(api);
        api.composerJson = new tensideApiComposerJson(api);
        api.appKernelPhp = new tensideApiAppKernelPhp(api);
        api.search = new tensideApiSearch(api);
        api.tasks = new tensideApiTasks(api);

        // Check if we have a stored key.
        if ($window.sessionStorage.apiKey !== undefined) {
            api.setKey($window.sessionStorage.apiKey);
        } else if ($window.localStorage.apiKey !== undefined) {
            api.setKey($window.localStorage.apiKey);
        }

        return api;
    }]).factory('Base64', function () {
        var keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

        return {
            encode: function (input) {
                var output = "";
                var chr1, chr2, chr3 = "";
                var enc1, enc2, enc3, enc4 = "";
                var i = 0;

                do {
                    chr1 = input.charCodeAt(i++);
                    chr2 = input.charCodeAt(i++);
                    chr3 = input.charCodeAt(i++);

                    enc1 = chr1 >> 2;
                    enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                    enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                    enc4 = chr3 & 63;

                    if (isNaN(chr2)) {
                        enc3 = enc4 = 64;
                    } else if (isNaN(chr3)) {
                        enc4 = 64;
                    }

                    output = output +
                        keyStr.charAt(enc1) +
                        keyStr.charAt(enc2) +
                        keyStr.charAt(enc3) +
                        keyStr.charAt(enc4);
                    chr1 = chr2 = chr3 = "";
                    enc1 = enc2 = enc3 = enc4 = "";
                } while (i < input.length);

                return output;
            },

            decode: function (input) {
                var output = "";
                var chr1, chr2, chr3 = "";
                var enc1, enc2, enc3, enc4 = "";
                var i = 0;

                // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
                var base64test = /[^A-Za-z0-9\+\/\=]/g;
                if (base64test.exec(input)) {
                    window.alert("There were invalid base64 characters in the input text.\n" +
                        "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
                        "Expect errors in decoding.");
                }
                input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

                do {
                    enc1 = keyStr.indexOf(input.charAt(i++));
                    enc2 = keyStr.indexOf(input.charAt(i++));
                    enc3 = keyStr.indexOf(input.charAt(i++));
                    enc4 = keyStr.indexOf(input.charAt(i++));

                    chr1 = (enc1 << 2) | (enc2 >> 4);
                    chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                    chr3 = ((enc3 & 3) << 6) | enc4;

                    output = output + String.fromCharCode(chr1);

                    if (enc3 != 64) {
                        output = output + String.fromCharCode(chr2);
                    }
                    if (enc4 != 64) {
                        output = output + String.fromCharCode(chr3);
                    }

                    chr1 = chr2 = chr3 = "";
                    enc1 = enc2 = enc3 = enc4 = "";

                } while (i < input.length);

                return output;
            }
        };
    });
}());
