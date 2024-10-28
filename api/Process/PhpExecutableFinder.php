<?php

declare(strict_types=1);

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
    private array $names = ['php-cli', 'php'];

    public function __construct(private readonly LoggerInterface|null $logger = null)
    {
    }

    /**
     * Finds the best matching PHP executable on the system. Contrary to
     * symfony/process PhpExecutableFinder we actually test if the binary is the same
     * version as the currently running web process.
     */
    public function find(array $paths = [], bool $discover = true): string|null
    {
        if (!$discover) {
            return $this->findBestBinary($paths);
        }

        if ($bin = \constant('PHP_BINARY')) {
            if (false !== ($suffix = strrchr(basename($bin), '-'))) {
                $php = substr($bin, 0, -\strlen($suffix));
                $paths[] = $php.'-cli';
                $paths[] = $php;
            }

            $paths[] = $bin.'-cli';
            $paths[] = $bin;

            $this->includePath($paths, \dirname($bin));
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

    public function getServerInfo(string $cli): array|null
    {
        $arguments = [$cli, '-q'];

        if ('' !== ($phar = \Phar::running(false))) {
            $arguments[] = $phar;
        } else {
            $arguments[] = \dirname(__DIR__).'/console';
        }

        $arguments[] = 'test';

        try {
            $process = new Process($arguments);
            $process->mustRun(null, array_map(static fn (): bool => false, $_ENV));
            $output = @json_decode(trim($process->getOutput()), true);

            if (null === $output) {
                throw new RuntimeException('Unexpected output from "'.implode(' ', $arguments).'": '.$process->getOutput());
            }

            return $output;
        } catch (RuntimeException $exception) {
            // Do not log every attempt to find a PHP binary (exit code 127 = Command not found)
            if (127 === $process->getExitCode()) {
                return null;
            }

            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }

            throw $exception;
        }
    }

    /**
     * Finds PHP executables within open_basedir or PATH environment variable.
     */
    private function findExecutables(): array
    {
        $results = [];

        if (\ini_get('open_basedir')) {
            $searchPath = explode(PATH_SEPARATOR, \ini_get('open_basedir'));
            $dirs = [];

            foreach ($searchPath as $path) {
                // Silencing against https://bugs.php.net/69240
                if (@is_dir($path)) {
                    $dirs[] = $path;
                } elseif (@is_executable($path) && \in_array(basename($path), $this->names, true)) {
                    $results[] = $path;
                }
            }
        } else {
            $dirs = [];

            if ($path = (getenv('PATH') ?: getenv('Path'))) {
                $dirs = explode(PATH_SEPARATOR, $path);
            }

            if ('\\' === \DIRECTORY_SEPARATOR) {
                $dirs[] = 'C:\xampp\php\\';
            }
        }

        $suffixes = [''];

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $pathExt = getenv('PATHEXT');
            $suffixes = array_merge(
                $suffixes,
                $pathExt ? explode(PATH_SEPARATOR, $pathExt) : ['.exe', '.bat', '.cmd', '.com'],
            );
        }

        foreach ($this->names as $name) {
            foreach ($suffixes as $suffix) {
                foreach ($dirs as $dir) {
                    if (
                        @is_file($file = $dir.\DIRECTORY_SEPARATOR.$name.$suffix)
                        && ('\\' === \DIRECTORY_SEPARATOR || is_executable($file))
                    ) {
                        $results[] = $file;
                    }
                }
            }
        }

        return $results;
    }

    private function findBestBinary(array $paths): string|null
    {
        $fallbackPath = null;
        $fallbackSapi = null;

        if ($openBasedir = \ini_get('open_basedir')) {
            $openBasedir = explode(PATH_SEPARATOR, $openBasedir);
        }

        foreach ($paths as $path) {
            // we only test for is_executable if no open_basedir restrictions are set or the
            // target is within allowed paths. If the path is not within open_basedir we can
            // still execute the binary on the command line and check the version.

            if ((!$openBasedir || $this->isAllowed($path, $openBasedir)) && !is_executable($path)) {
                continue;
            }

            try {
                $info = $this->getServerInfo($path);
            } catch (RuntimeException) {
                continue;
            }

            if (!\is_array($info)) {
                continue;
            }

            if ('cli' === $info['sapi'] && version_compare(PHP_VERSION, $info['version'], 'eq')) {
                return $path;
            }

            $vWeb = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
            $vCli = vsprintf('%s.%s', explode('.', (string) $info['version']));

            // Allow fallback to another patch version of the same PHP major/minor and prefer
            // a CLI SAPI over e.g. a CGI SAPI.
            if (
                (null === $fallbackPath || ('cli' !== $fallbackSapi && 'cli' === $info['sapi']))
                && version_compare($vWeb, $vCli, 'eq')
            ) {
                $fallbackPath = $path;
                $fallbackSapi = $info['sapi'];
            }
        }

        return $fallbackPath;
    }

    /**
     * Tests if the given path is within any of the given directories.
     */
    private function isAllowed(string $path, array $dirs): bool
    {
        foreach ($dirs as $dir) {
            if (str_starts_with($path, (string) $dir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds the all binaries for given path to paths array.
     */
    private function includePath(array &$paths, string $path): void
    {
        foreach ($this->names as $name) {
            $paths[] = $path.\DIRECTORY_SEPARATOR.$name;
        }
    }
}
