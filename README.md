This is the Contao Manager
==================================

[![Version](http://img.shields.io/packagist/v/contao/contao-manager.svg?style=flat-square)](https://packagist.org/packages/contao/contao-manager)
[![Stable Build Status](http://img.shields.io/travis/contao/contao-manager/master.svg?style=flat-square&label=stable%20build)](https://travis-ci.org/contao/contao-manager)
[![License](http://img.shields.io/packagist/l/contao/contao-manager.svg?style=flat-square)](http://spdx.org/licenses/MIT)

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

### 3. Updating composer dependencies

Make sure you run

`$ php composer.phar install` so the whole PHP dependencies are pulled
as well.

### 4. Setting the DirectoryIndex

Make sure your DirectoryIndex points to `/web` because that's where the
project runs in (regular Symfony application).

### 5. Setting the COMPOSER environment variable

The underlying software that handles all the Composer commands is called
`Tenside`. By default, `Tenside` thinks of managing an installation from
within the standard Symfony application `web` folder but it can be
changed to anything you like.

As you run within the `web` folder, `Tenside` will check for one directory
above it for the existence of a `composer.json` file and start managing
from there. While developing this means, that it will start to manage the
package manager itself which is likely not what you want.
For that matter, set the `COMPOSER` environment variable to a different
location (`test-dir` in the package manager root in that case).

Example for Apache (add to file `/path/to/my/contao-manager/web/.htaccess`):

```
SetEnv COMPOSER /path/to/test-dir/composer.json
```

This will force tenside to use the defined location as installation root
(the file itself does not have to exist, only the parenting directory has
to).

In the end, Tenside behaves exactly the same as Composer, see
[composer docs](https://getcomposer.org/doc/03-cli.md#composer)

### 6. Development and Debug modes

By default, debugging is disabled and the package manager runs in `prod`
mode. To change this, you can set yet another two environment variables:

```
SetEnv SYMFONY_ENV dev
SetEnv SYMFONY_DEBUG 1
```

This enables extended logging and debugging features especially useful
during development.

### 7. Accessing the API documentation

You can see all available API calls thanks to the fantastic 
NelmioApiBundle when accessing the route `/assets/api-doc`.
Note that this is only available in `dev` mode .

For a pre generated version of documentation, see [doc/API.md][1]

[1]: https://github.com/contao/contao-manager/blob/master/doc/API.md

### 8. Tips & tricks

If you do have gulp installed globally already or you have
been developing on the package manager before, it's very likely you pull
the latest changes using git and then want to do both, an `npm install`
as well as a `composer update` and finally also build the JS and CSS
files using `gulp` again. To make this easier, you can just use

`$ ./node_modules/.bin/gulp update`
