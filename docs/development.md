# Developing on Contao Manager

The *Contao Manager* development environment supports [hot-reloading]
of Vue components in the frontend. To develop the application, two
servers are required.


## PHP Backend API

The backend is a RESTful API written in PHP using the Symfony framework.
PHP offers an internal web server for development purposes, which we
can use to run the backend API.

```
$ php -S 127.0.0.1:8000 --docroot=web/
```

By default, the API places all Contao files in a `test-dir` folder inside
your GIT project root. You can override the location of Contao by setting
the `COMPOSER` environment variable according to the [Composer documentation].

*Be aware that the server must run on port 8000 for the dev frontend to
work correctly.*


## Javascript Frontend UI

The frontend is a SPA (Single Page Application) build using Vue.js.
To start the frontend server run the following command:

```
$ npm run dev
```

This will automatically open your default browser with the frontend.


[hot-reloading]: https://vue-loader.vuejs.org/en/features/hot-reload.html
[Composer documentation]: https://getcomposer.org/doc/03-cli.md#composer
