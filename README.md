tenside/ui
==========

Setup guide
-----------

The tenside UI depend on some third-party assets, precompilers, and so on. All dependencies are installed locally,
expect the node package manager `npm` which must be installed on your system.

**Hint** Many distributions come with very old node/npm packages, we recommend to use the NodeSource binary
distributions (formerly Chris Lea's Launchpad PPA).
Support for this repository, along with its scripts, can be found on
GitHub at [nodesource/distributions](https://github.com/nodesource/distributions).

We have bundled the [doc/node_setup_dev](doc/node_setup_dev) shell script which will setup the repository on
Debian/Ubuntu for you.

When npm is ready, you can install the needed packages.

```bash
# first install all node packages
npm install --save-dev

# now install third-party assets
./node_modules/.bin/gulp install
```

**Hint** If you have installed gulp globally, you can simply run `gulp install`.

Build production ready distribution
-----------------------------------

To build the production ready distribution, run `./node_modules/.bin/gulp build`.

Development
-----------

For development you may simply run `./node_modules/.bin/gulp`, which will watch on all assets and live rebuild them on
change. The gulpfile comes with a [livereload](https://github.com/vohof/gulp-livereload) configuration,
we recommend to install the
[chrome live reload](https://chrome.google.com/webstore/detail/livereload/jnihajbhpnppcggbcgedagnkighmdlei/related)
add-on for the live reload capability.
