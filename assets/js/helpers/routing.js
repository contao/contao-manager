'use strict';

const crossroads   = require('crossroads');
const history      = require('history').createHistory();
const forIn        = require('lodash/forIn');
const request      = require('./request.js');

// Route definitions (do not define dynamically so they get bundled by browserify)
var Routing = {
    routeDefinitions: {
        'install': require('./../routes/install.js'),
        'login': require('./../routes/login.js'),
        'packages': require('./../routes/packages.js'),
        'app-kernel': require('./../routes/app-kernel.js'),
        'composer-json': require('./../routes/composer-json.js'),
        'self-test': require('./../routes/self-test.js'),
        'logout': require('./../routes/logout.js')
    },

    _initialized: false,
    router: null,
    routes: [],
    currentRoute: null,

    _initialize: function () {
        var self = this;
        if (this._initialized) {
            return;
        }

        // Router
        this.router = crossroads.create();

        forIn(this.routeDefinitions, function (routeDef, routeName) {
            var route = self.router.addRoute(routeDef.path);
            route.name = routeName;
            route.preController = routeDef.preController;
            route.controller = routeDef.controller;
            self.routes[routeName] = route;
        });

        this._initialized = true;
    },

    getRouter: function () {
        this._initialize();
        return this.router;
    },

    getRoutes: function () {
        this._initialize();
        return this.routes;
    },

    getRoute: function (routeName) {
        this._initialize();
        var routes = this.getRoutes();
        return routes[routeName];
    },

    generateUrl: function (routeName, lang) {
        this._initialize();
        lang = typeof lang !== 'undefined' ? lang : this.getLanguage();
        var route = this.getRoute(routeName);
        return route.interpolate({locale: lang});
    },

    isCurrentRoute: function (routeName) {
        return routeName === currentRoute;
    },

    setCurrentRoute: function (routeName) {
        this.currentRoute = routeName;
    },

    redirect: function (routeName, lang) {
        this._initialize();
        history.push(this.generateUrl(routeName, lang));
    },

    getHistory: function () {
        return history;
    },

    getLanguage: function () {
        var lang = 'en';

        if (undefined !== document
            && undefined !== document.getElementsByTagName('html')[0]
            && undefined !== document.getElementsByTagName('html')[0].lang) {
            lang = document.getElementsByTagName('html')[0].lang;
        }

        return lang;
    }
};

module.exports = Routing;
