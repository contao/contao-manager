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

    TENSIDE_API.factory('$tensideApi', ['$http', function ($http) {
        var
            http = $http,
            tensideApiConnection = function() {
                var self = this,
                    apiUrl,
                    version;
                self.setBaseUrl = function(url) {
                    apiUrl = url;
                };

                self.getBaseUrl = function() {
                    return apiUrl;
                };

                self.setVersion = function(ver) {
                    version = ver;
                };

                self.getVersion = function() {
                    return version;
                };

                self.endpoint = function(endpoint) {
                    if (version === undefined) {
                        version = 'v1';
                    }
                    return self.getBaseUrl() + 'api/' + version + '/' + endpoint;
                };

                self.packages = new tensideApiPackages(self);
                self.composerJson = new tensideApiComposerJson(self);
            },
            tensideApiPackages = function (tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function(name) {
                        if (name) {
                            return endpoint() + '/' + name;
                        }
                        return api.endpoint('packages');
                    }
                    ;
                self.list = function(all) {
                    return http.get(endpoint(), all ? {params: {all: ''}} : {});
                };
                self.get = function(name) {
                    return http.get(endpoint(name));
                };
                self.put = function(data) {
                    return http.put(endpoint(data.name), data);
                };
                self.delete = function () {
                    return http.delete(endpoint(data.name));
                };
            },
            tensideApiComposerJson = function (tensideApi) {
                var self = this,
                    api = tensideApi,
                    endpoint = function() {
                        return api.endpoint('composer.json');
                    }
                    ;
                self.get = function() {
                    return http.get(endpoint(), {'transformResponse': []});
                };
                self.put = function(data) {
                    return http.put(endpoint(), data);
                };
            };

        return new tensideApiConnection();
    }]);
}());
