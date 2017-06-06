# Contao Manager

Contao is an Open Source PHP Content Management System.
Contao Manager is a graphical tool to manage a Contao installation.
Visit the [project website][Contao] for more information.

The application is distributed as a Phar file, you should only work
with this repository if you want to help with development of the app.


## System requirements

- PHP >= 5.5.9 or PHP 7.*
- PHP Intl extension
- PHP OpenSSL extension
- *allow_url_fopen* must be enabled in PHP
- *proc_open* and *proc_close* PHP functions

**Contao Manager does currently not work on Windows, support for
Windows will be added in a later version (see [issue #66])


## Installation

Install PHP dependencies using [Composer].

```
$ composer.phar install
```


Install Javascript dependencies using NPM.

```
$ npm install
```


## Documentation

 - [Development](docs/development.md)
 - [Build the Phar](docs/build-phar.md)


## License

Contao Manager is licensed under the terms of the LGPLv3.
The full license text is available in the LICENSE file.



[Composer]: http://getcomposer.org
[Contao]: https://contao.org
[issue #66]: https://github.com/contao/contao-manager/issues/66
