const crossroads   = require('crossroads');
const history      = require('history').createHistory();


var _initialized = false;
var router = null;
var routes = [];

var _initialize = function() {
    if (_initialized) {
        return;
    }

    // Router
    router = crossroads.create();

    // Routes
    routes['login'] = router.addRoute('/{locale}/login');
    routes['install'] = router.addRoute('/{locale}/install');
    routes['packages'] = router.addRoute('/{locale}/packages');

    _initialized = true;
};

var _getLanguage = function() {
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

var redirect = function(routeName, lang) {
    _initialize();
    lang = typeof lang !== 'undefined' ? lang : _getLanguage();
    var route = getRoute(routeName);
    var newLocation = route.interpolate({locale: lang});

    history.push(newLocation);
};

var getHistory = function() {
    return history;
};

module.exports = {
    getRouter: getRouter,
    getRoutes: getRoutes,
    getRoute: getRoute,
    redirect: redirect,
    getHistory: getHistory
};
