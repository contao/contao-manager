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
            baseUrl,
            tensideApi = {};

        var endpoint = function(endpoint, version) {
            if (version === undefined) {
                version = 'v1';
            }
            return tensideApi.getBaseUrl() + 'api/' + version + '/' + endpoint;
        };

        tensideApi.setBaseUrl = function(url) {
            baseUrl = url;
        };

        tensideApi.getBaseUrl = function() {
            return baseUrl;
        };

        tensideApi.packages = (function() {
            this.list = function(all) {
                return $http.get(endpoint('packages'), all ? {params: {all: ''}} : {});
            };
            this.get = function(name) {
                return $http.get(endpoint('packages') + '/' + name);
            };
            this.put = function(name, data) {
                return $http.put(endpoint('packages') + '/' + name, data);
            };
            this.delete = function () {
                return $http.delete(endpoint('packages') + '/' + name);
            };

            return this;
        })();

        tensideApi.composerJson = (function() {
            this.get = function() {
                return $http.get(endpoint('composer.json'), {'transformResponse': []});
            };
            this.put = function(data) {
                return $http.put(endpoint('composer.json'), data);
            };

            return this;
        })();

        return tensideApi;
    }]);
}());
