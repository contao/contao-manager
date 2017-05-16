<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Process;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PhpExecutableFinder
{
    private $names = ['php-cli', 'php'];

    /**
     * Finds the best matching PHP executable on the system.
     *
     * Contrary to symfony/process PhpExecutableFinder we actually test if the binary is
     * the same version as the currently running web process.
     *
     * @return string|null
     */
    public function find()
    {
        $paths = [];

        if (PHP_BINARY) {
            $paths[] = PHP_BINARY.'-cli';
            $paths[] = PHP_BINARY;

            if (false !== ($suffix = strrchr(basename(PHP_BINARY), '-'))) {
                $php = substr(PHP_BINARY, 0, -strlen($suffix));
                $paths[] = $php.'-cli';
                $paths[] = $php;
            }

            $this->includePath($paths, dirname(PHP_BINARY));
        }

        if (PHP_BINDIR) {
            $this->includePath($paths, PHP_BINDIR);
        }

        if ($php = getenv('PHP_PATH')) {
            $paths[] = $php;
        }

        if ($php = getenv('PHP_PEAR_PHP_BIN')) {
            $paths[] = $php;
        }

        $paths = array_merge($paths, $this->findExecutables());

        return $this->findBinary(array_unique($paths));
    }

    /**
     * Finds PHP executables within open_basedir or PATH environment variable.
     *
     * @return array
     */
    private function findExecutables()
    {
        $results = [];

        if (ini_get('open_basedir')) {
            $searchPath = explode(PATH_SEPARATOR, ini_get('open_basedir'));
            $dirs = [];

            foreach ($searchPath as $path) {
                // Silencing against https://bugs.php.net/69240
                if (@is_dir($path)) {
                    $dirs[] = $path;
                } else {
                    if (in_array(basename($path), $this->names) && @is_executable($path)) {
                        $results[] = $path;
                    }
                }
            }
        } else {
            $dirs = explode(PATH_SEPARATOR, getenv('PATH') ?: getenv('Path'));

            if ('\\' === DIRECTORY_SEPARATOR) {
                $dirs[] = 'C:\xampp\php\\';
            }
        }

        $suffixes = [''];
        if ('\\' === DIRECTORY_SEPARATOR) {
            $pathExt = getenv('PATHEXT');
            $suffixes = array_merge(
                $suffixes,
                $pathExt ? explode(PATH_SEPARATOR, $pathExt) : ['.exe', '.bat', '.cmd', '.com']
            );
        }

        foreach ($this->names as $name) {
            foreach ($suffixes as $suffix) {
                foreach ($dirs as $dir) {
                    if (@is_file($file = $dir . DIRECTORY_SEPARATOR . $name . $suffix)
                        && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))
                    ) {
                        $results[] = $file;
                    }
                }
            }
        }

        return $results;
    }

    private function findBinary(array $paths)
    {
        $fallback = null;

        if ($openBasedir = ini_get('open_basedir')) {
            $openBasedir = explode(PATH_SEPARATOR, $openBasedir);
        }

        foreach ($paths as $path) {
            // we only test for is_executable if no open_basedir restrictions are set
            // or the target is within allowed paths. If the path is not within open_basedir
            // we can still execute the binary on the command line and check the version.

            if ((!$openBasedir || $this->isAllowed($path, $openBasedir)) && !is_executable($path)) {
                continue;
            }

            try {
                $process = (new Process(escapeshellcmd($path)." -r 'echo PHP_VERSION;'"))->mustRun();
                $version = trim($process->getOutput());
            } catch (ProcessFailedException $e) {
                continue;
            }

            if (version_compare(PHP_VERSION, $version, 'eq')) {
                return $path;
            }

            $vWeb = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
            $vCli = vsprintf('%s.%s', explode('.', $version));

            if (null === $fallback && version_compare($vWeb, $vCli, 'eq')) {
                $fallback = $path;
                continue;
            }
        }

        return $fallback;
    }

    /**
     * Tests if the given path is within any of the given directories.
     *
     * @param string $path
     * @param array $dirs
     *
     * @return bool
     */
    private function isAllowed($path, array $dirs)
    {
        foreach ($dirs as $dir) {
            if (0 === strpos($path, $dir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds the all binaries for given path to paths array.
     *
     * @param array  $paths
     * @param string $path
     */
    private function includePath(array &$paths, $path)
    {
        foreach ($this->names as $name) {
            $paths[] = $path.DIRECTORY_SEPARATOR.$name;
        }
    }
}
