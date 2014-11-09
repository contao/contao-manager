tenside/ui
==========

Setup guide
-----------

The tenside UI depend on some third-party assets, precompilers, and so on. All dependencies are installed locally,
expect the node package manager `npm` which must be installed on your system.

**Hint** Ubuntu comes with a very old node/npm packages, we recommend to use Chris Lea's PPA to get the latest releases.
http://tecadmin.net/install-latest-nodejs-npm-on-ubuntu/

```bash
# for debian/ubuntu without ppa
sudo apt-get install npm

# for ubuntu with ppa
sudo apt-get install nodejs
```

Now you can install the dependent packages.

```bash
# first install all node packages
npm install --save-dev

# now install third-party assets
./node_modules/.bin/gulp install
```

**Hint** If you have installed gulp globally, you can simply run `./node_modules/.bin/gulp install`.

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
