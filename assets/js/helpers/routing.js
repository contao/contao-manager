import crossroads   from 'crossroads';
import { createHistory } from 'history';
import routeDefinitions from '../routes';
import forIn        from 'lodash/forIn';

const history = createHistory();

// Route definitions (do not define dynamically so they get bundled by browserify)
export default {
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

        var baseHref = this.getBaseHref();
        forIn(routeDefinitions, function (routeDef) {
            var route = self.router.addRoute(baseHref + routeDef.path);
            route.name = routeDef.name;
            route.preController = routeDef.preController;
            route.controller = routeDef.controller;
            self.routes[routeDef.name] = route;
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
        return routeName === this.currentRoute;
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
    },

    getBaseHref: function () {
        var href = '';

        if (undefined !== document
            && undefined !== document.getElementsByTagName('base')[0]
            && undefined !== document.getElementsByTagName('base')[0].attributes.getNamedItem('href')) {
            href = document.getElementsByTagName('base')[0].attributes.getNamedItem('href').nodeValue;
            if ("/" === href.substr(href.length - 1)) {
                href = href.substr(0, href.length - 1);
            }
        }

        return href;
    }
}
