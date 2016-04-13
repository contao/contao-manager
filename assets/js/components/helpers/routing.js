const crossroads   = require('crossroads');
const history      = require('history').createHistory();


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
    routes['login'] = router.addRoute('/{locale}/login');
    routes['install'] = router.addRoute('/{locale}/install');
    routes['packages'] = router.addRoute('/{locale}/packages');
    routes['app-kernel'] = router.addRoute('/{locale}/files/app-kernel');
    routes['composer-json'] = router.addRoute('/{locale}/files/composer-json');

    _initialized = true;
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
