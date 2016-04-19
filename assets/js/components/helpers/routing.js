const crossroads   = require('crossroads');
const history      = require('history').createHistory();
const _            = require('lodash');

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

    // Routes
    var routesDef = {
        'install': {
            path: '/{locale}/install',
            requirement: function(results) {
                // Fully configured, never access this route
                if (true === results['tenside_configured']
                    && true === results['project_created']
                    && true === results['project_installed']
                ) {
                    return 'login';
                }

                // Configured but not logged in yet (and not installed or not
                // created - otherwise the if above would have redirected
                // already)
                if (true === results['tenside_configured']
                    && false === results['user_loggedIn']
                ) {
                    return 'login';
                }

                return true;
            }
        },
        'login': {
            path: '/{locale}/login',
            requirement: function(results) {
                // Already logged in
                if (true === results['user_loggedIn']) {
                    return 'packages'
                }

                // Configured but not installed (needs to login)
                if (true === results['tenside_configured']
                    && (false === results['project_created']
                    || false === results['project_installed'])
                ) {
                    return true;
                }

                return true;
            }
        },
        'packages': {
            path: '/{locale}/packages',
            requirement: _tensideOkAndLoggedIn
        },
        'app-kernel': {
            path: '/{locale}/files/app-kernel',
            requirement: _tensideOkAndLoggedIn
        },
        'composer-json': {
            path: '/{locale}/files/composer-json',
            requirement: _tensideOkAndLoggedIn
        }
    };

    _.forIn(routesDef, function(routeDef, routeName) {
        var route = router.addRoute(routeDef.path);
        route.name = routeName;
        route.requirement = routeDef.requirement;
        route.controller  = routeDef.controller;
        routes[routeName] = route;
    });

    _initialized = true;
};

var _tensideOk = function(results) {
    if (false === results['tenside_configured']
        || false === results['project_created']
        || false === results['project_installed']
    ) {
        return 'install';
    }

    return true;
};

var _tensideOkAndLoggedIn = function(results) {
    var tensideOk = _tensideOk(results);

    if (true !== tensideOk) {
        return tensideOk;
    }

    if (false === results['user_loggedIn']) {
        return 'login';
    }

    return true;
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

module.exports = {
    getRouter: getRouter,
    getRoutes: getRoutes,
    getRoute: getRoute,
    generateUrl: generateUrl,
    isCurrentRoute: isCurrentRoute,
    redirect: redirect,
    getHistory: getHistory,
    getLanguage: getLanguage,
    setCurrentRoute: setCurrentRoute
};
