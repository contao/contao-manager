# Developing on Contao Manager

The *Contao Manager* development environment supports [hot-reloading]
of Vue components in the frontend. To develop the application, two
servers are required.


## PHP API Client

The PHP API client handles RESTful requests to composer. It is based on
[tenside/core] and [tenside/core-bundle] API. To start the API, we need
two things:

1. Composer needs to know where the test installation should be
    located. This is done by defining an environment variable named
    `COMPOSER`.
2. The PHP server needs to be started on localhost:8080.

The most simple way to achieve this to run the build-in PHP server
provided by the Symfony console. Simply run the following command:

```
$ export COMPOSER=/path/to/your/test/composer.json && php api/console server:run
```

The path to the composer.json should point to the root directory of
the Contao installation to manage.


## Javascript Frontend UI

The frontend is build using Vue.js. To start the NPM server run the
following command.

```
$ npm run dev
```

This will automatically open your default browser with the frontend.


[hot-reloading]: https://vue-loader.vuejs.org/en/features/hot-reload.html
[tenside/core]: https://github.com/tenside/core
[tenside/core-bundle]: https://github.com/tenside/core-bundle
