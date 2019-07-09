# Building the Contao Manager Phar

The build process requires several parts. To ease everything,
you can use [Phing] and run the provided `build.xml` file.

Phing can also be installed by adding `phing/phing` to the 
`composer.json`. You also need install [Box], e.g. via 
homebrew.

Simply install Phing on your local computer and run it in the
root directory. The result will be a `contao-manager.phar` file
in the root.

If you want to test your changes and want to create a phar from
the files you have currently checked out, run 
`[vendor/bin/]phing debug`.


[Phing]: http://phing.info
[Box]: https://github.com/humbug/box/
