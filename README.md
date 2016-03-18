This is the contao package manager
==================================

[![Version](http://img.shields.io/packagist/v/contao/package-manager.svg?style=flat-square)](https://packagist.org/packages/contao/package-manager)
[![Stable Build Status](http://img.shields.io/travis/contao/package-manager/master.svg?style=flat-square&label=stable%20build)](https://travis-ci.org/contao/package-manager)
[![License](http://img.shields.io/packagist/l/contao/package-manager.svg?style=flat-square)](http://spdx.org/licenses/MIT)

## Build yourself

### 1. Install the node.js dependencies:

`$ npm install`

This will make sure, everything needed to build the source JS and CSS files etc.
can be compiled into their respective targets.
If you want to know what dependencies are needed, check the `package.json`
file (or the node.js documentation).

###  2. Gulp

Because calling multiple commands after each other and in the correct
order everytime you change something, we use Gulp to define tasks. A simple

`$ ./node_modules/.bin/gulp`

will execute the `default` task defined in the `gulpfile.js` and thus build
the bundled Javascript and CSS files.

If you want to change something on the source files and have Gulp rebuild
all the files everytime you save your changes, simply use

`$ ./node_modules/.bin/gulp watch`

which will keep watching all source files until you end the process.

Note: For production you should use

`$ ./node_modules/.bin/gulp --production`

because that will enable minifying (uglyfying) JS as well as CSS files.

### 3. Setting the DirectoryIndex

Make sure your DirectoryIndex points to `/web` because that's where the
project runs in (regular Symfony application).

### 4. Setting the COMPOSER environment variable

The underlying software that handles all the Composer commands is called
`Tenside`. By default, `Tenside` thinks of managing an installation from
within the standard Symfony application `web` folder but it can be
changed to anything you like.

As you run within the `web` folder, `Tenside` will check for one directory
above it and start managing from there. While developing this means, that
it will start to manage the package manager itself which is likely not
what you want. For that matter, set the `COMPOSER` environment variable
to a different directory (`test-dir` in the package manager root in that
case):

```
SetEnv COMPOSER /path/to/my/package-manager/test-dir/web
```

Note the virual `web` folder. Obviously that's an example for Apache.
