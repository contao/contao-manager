This is the contao package manager
==================================

This provides the web ui for the tenside/core.

Prerequisites
-------------

The tenside UI depend on some third-party assets, pre compilers, and so on. All
dependencies are installed locally, expect the node package manager `npm` which
must be installed on your system.

**Hint:** Many distributions come with very old node/npm packages, we recommend
to use the NodeSource binary distributions (fka Chris Lea's Launchpad PPA).
Support for this repository, along with its scripts, can be found on GitHub at
[nodesource/distributions](https://github.com/nodesource/distributions).

We have bundled the [doc/node_setup_dev](doc/node_setup_dev) shell script which
will setup the repository on Debian/Ubuntu for you.

Building a phar
---------------

We use [pharpiler](https://github.com/cyberspectrum/pharpiler) so simply issue
`vendor/bin/pharpiler compile` and a phar file will get created.

Building the assets by hand
---------------------------

1. First off, ensure the node dependencies are installed.
```bash
npm install --save-dev
```

2. Now let gulp install all needed third party libraries.
```bash
./node_modules/.bin/gulp install
```

3. Finally build the assets for distribution.
```bash
./node_modules/.bin/gulp build
```

Developing the ui
-----------------

For development you may simply run `./node_modules/.bin/gulp`, which will watch
on all assets and live rebuild them on change. The gulpfile comes with a 
[livereload](https://github.com/vohof/gulp-livereload) configuration, we 
recommend to install the
[chrome live reload](https://chrome.google.com/webstore/detail/livereload/jnihajbhpnppcggbcgedagnkighmdlei/related)
add-on for the live reload capability.
