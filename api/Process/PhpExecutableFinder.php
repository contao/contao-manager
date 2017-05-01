<?php

namespace Contao\ManagerApi\Process;

use Symfony\Component\Process\PhpExecutableFinder as BasePhpExecutableFinder;

class PhpExecutableFinder extends BasePhpExecutableFinder
{
    private $suffixes = array('.exe', '.bat', '.cmd', '.com');

    /**
     * This is a duplicate of the parent method, but we don't use the ExecutableFinder.
     *
     * {@inheritdoc}
     */
    public function find($includeArgs = true)
    {
        $args = $this->findArguments();
        $args = $includeArgs && $args ? ' '.implode(' ', $args) : '';

        // HHVM support
        if (defined('HHVM_VERSION')) {
            return (getenv('PHP_BINARY') ?: PHP_BINARY).$args;
        }

        // PHP_BINARY return the current sapi executable
        if (PHP_BINARY && in_array(PHP_SAPI, array('cli', 'cli-server', 'phpdbg')) && is_file(PHP_BINARY)) {
            return PHP_BINARY.$args;
        }

        if ($php = getenv('PHP_PATH')) {
            if (!is_executable($php)) {
                return false;
            }

            return $php;
        }

        if ($php = getenv('PHP_PEAR_PHP_BIN')) {
            if (is_executable($php)) {
                return $php;
            }
        }

        $dirs = array(PHP_BINDIR);
        if ('\\' === DIRECTORY_SEPARATOR) {
            $dirs[] = 'C:\xampp\php\\';
        }

        return $this->findExecutable('php', null, $dirs);
    }

    /**
     * This is a duplicate of ExecutableFinder::find() but we're checking the $extraDirs first.
     */
    private function findExecutable($name, $default = null, array $extraDirs = array())
    {
        if (ini_get('open_basedir')) {
            $searchPath = explode(PATH_SEPARATOR, ini_get('open_basedir'));
            $dirs = array();
            foreach ($searchPath as $path) {
                // Silencing against https://bugs.php.net/69240
                if (@is_dir($path)) {
                    $dirs[] = $path;
                } else {
                    if (basename($path) == $name && @is_executable($path)) {
                        return $path;
                    }
                }
            }
        } else {
            $dirs = array_merge(
                $extraDirs,
                explode(PATH_SEPARATOR, getenv('PATH') ?: getenv('Path'))
            );
        }

        $suffixes = array('');
        if ('\\' === DIRECTORY_SEPARATOR) {
            $pathExt = getenv('PATHEXT');
            $suffixes = array_merge($suffixes, $pathExt ? explode(PATH_SEPARATOR, $pathExt) : $this->suffixes);
        }
        foreach ($suffixes as $suffix) {
            foreach ($dirs as $dir) {
                if (@is_file($file = $dir.DIRECTORY_SEPARATOR.$name.$suffix) && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))) {
                    return $file;
                }
            }
        }

        return $default;
    }
}
