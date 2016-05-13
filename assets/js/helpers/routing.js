'use strict';

const crossroads   = require('crossroads');
const history      = require('history').createHistory();
const forIn        = require('lodash/forIn');
const request      = require('./request.js');

// Route definitions (do not define dynamically so they get bundled by browserify)
var routeDefinitions = {
    'install':          require('./../routes/install.js'),
    'login':            require('./../routes/login.js'),
    'packages':         require('./../routes/packages.js'),
    'app-kernel':       require('./../routes/app-kernel.js'),
    'composer-json':    require('./../routes/composer-json.js'),
    'self-test':        require('./../routes/self-test.js'),
    'logout':           require('./../routes/logout.js')
};
var _initialized = false;
var router = null;
var routes = [];
var currentRoute = null;

var _initialize = function() {
    if (_initialized) {
        return;
    }

    // Router
    router = crossroads.create();

    forIn(routeDefinitions, function(routeDef, routeName) {
        var route = router.addRoute(routeDef.path);
        route.name = routeName;
        route.preController = routeDef.preController;
        route.controller  = routeDef.controller;
        routes[routeName] = route;
    });

    _initialized = true;
};

var getRouter = function()
{
    _initialize();
    return router;
};

var getRoutes = function() {
    _initialize();
    return routes;
};

var getRoute = function(routeName) {
    _initialize();
    var routes = getRoutes();
    return routes[routeName];
};

var generateUrl = function (routeName, lang) {
    _initialize();
    lang = typeof lang !== 'undefined' ? lang : getLanguage();
    var route = getRoute(routeName);
    return route.interpolate({locale: lang});
};

var isCurrentRoute = function(routeName) {
    return routeName === currentRoute;
};

var setCurrentRoute = function(routeName) {
    currentRoute = routeName;
};

var redirect = function(routeName, lang) {
    _initialize();
    history.push(generateUrl(routeName, lang));
};

var getHistory = function() {
    return history;
};

var getLanguage = function() {
    var lang = 'en';

    if (undefined !== document
        && undefined !== document.getElementsByTagName('html')[0]
        && undefined !== document.getElementsByTagName('html')[0].lang) {
        lang = document.getElementsByTagName('html')[0].lang;
    }

    return lang;
};

module.exports = {
    getRouter: getRouter,
    getRoutes: getRoutes,
    getRoute: getRoute,
    generateUrl: generateUrl,
    isCurrentRoute: isCurrentRoute,
    setCurrentRoute: setCurrentRoute,
    redirect: redirect,
    getHistory: getHistory,
    getLanguage: getLanguage
};
