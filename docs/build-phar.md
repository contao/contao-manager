# Building the Contao Manager Phar

The build process requires several parts. To ease everything,
you can use [Phing] and run the provided `build.xml` file.

Simply install Phing and run it in the root directory. The result will be a
`contao-manager.phar` file in the root.

```
$ composer bin all install
$ composer run phing
```

If you want to test your changes and want to create a phar from
the files you have currently checked out, run
`composer run phing debug`.


[Phing]: http://phing.info
