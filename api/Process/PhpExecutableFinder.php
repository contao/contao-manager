<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Process;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class PhpExecutableFinder
{
    private $names = ['php-cli', 'php'];

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Finds the best matching PHP executable on the system.
     * Contrary to symfony/process PhpExecutableFinder we actually test if the binary is
     * the same version as the currently running web process.
     *
     * @param array $paths
     * @param bool  $discover
     *
     * @return null|string
     */
    public function find(array $paths = [], $discover = true)
    {
        if (!$discover) {
            return $this->findBestBinary($paths);
        }

        if ($bin = constant('PHP_BINARY')) {
            if (false !== ($suffix = strrchr(basename($bin), '-'))) {
                $php = substr($bin, 0, -strlen($suffix));
                $paths[] = $php.'-cli';
                $paths[] = $php;
            }

            $paths[] = $bin.'-cli';
            $paths[] = $bin;

            $this->includePath($paths, dirname($bin));
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

        $paths[] = '/usr/bin/php';

        $paths = array_merge($paths, $this->findExecutables());
        $paths = array_unique($paths);

        ksort($paths);

        return $this->findBestBinary($paths);
    }

    /**
     * @param string $cli
     *
     * @return array|null
     */
    public function getServerInfo($cli)
    {
        $arguments = ['-q'];

        if ('' !== ($phar = \Phar::running(false))) {
            $arguments[] = $phar;
        } else {
            $arguments[] = dirname(__DIR__).'/console';
        }

        $arguments[] = 'test';

        $commandline = escapeshellcmd($cli).' '.implode(' ', array_map('escapeshellarg', $arguments));

        try {
            $process = (new Process($commandline))->mustRun(null, array_map(function () { return false; }, $_ENV));
            $output = @json_decode(trim($process->getOutput()), true);

            if (null === $output) {
                throw new RuntimeException('Unexpected output from "'.$commandline.'": '.$process->getOutput());
            }

            return $output;
        } catch (RuntimeException $e) {
            if (null !== $this->logger) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }

            return null;
        }
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
                } elseif (in_array(basename($path), $this->names, true) && @is_executable($path)) {
                    $results[] = $path;
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
                    if (@is_file($file = $dir.DIRECTORY_SEPARATOR.$name.$suffix)
                        && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))
                    ) {
                        $results[] = $file;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @param array $paths
     *
     * @return string|null
     */
    private function findBestBinary(array $paths)
    {
        $fallback = null;
        $sapi = null;

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

            $info = $this->getServerInfo($path);

            if (!is_array($info)) {
                continue;
            }

            if ('cli' === $info['sapi'] && version_compare(PHP_VERSION, $info['version'], 'eq')) {
                return $path;
            }

            $vWeb = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
            $vCli = vsprintf('%s.%s', explode('.', $info['version']));

            if ((null === $fallback || ('cli' !== $sapi && $info['sapi'] === 'cli'))
                && version_compare($vWeb, $vCli, 'eq')
            ) {
                $fallback = $path;
                $sapi = $info['sapi'];
            }
        }

        return $fallback;
    }

    /**
     * Tests if the given path is within any of the given directories.
     *
     * @param string $path
     * @param array  $dirs
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
