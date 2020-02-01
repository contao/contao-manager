<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\System;

use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\Process\Forker\DisownForker;
use Contao\ManagerApi\Process\Forker\NohupForker;
use Contao\ManagerApi\Process\Forker\WindowsStartForker;
use Contao\ManagerApi\Process\PhpExecutableFinder;

class ServerInfo
{
    public const PLATFORM_WINDOWS = 'windows';
    public const PLATFORM_UNIX = 'unix';

    private const PHP_BINARIES = [
        '/opt/plesk/php/{major}.{minor}/bin/php',
        '/bin/php{major}{minor}',
        '/opt/RZphp{major}{minor}/bin/php-cli',
        '/opt/alt/php{major}{minor}/usr/bin/php',
        '/opt/php-{major}.{minor}.{release}/bin/php',
        '/opt/php-{major}.{minor}/bin/php',
        '/opt/php{major}.{minor}/bin/php',
        '/opt/php{major}{minor}/bin/php',
        '/opt/php{major}/bin/php',
        '/usr/bin/php{major}.{minor}-cli',
        '/usr/bin/php{major}.{minor}',
        '/usr/bin/php{major}{minor}',
        '/usr/bin/php{major}{minor}/php{major}',
        '/usr/bin/php{major}',
        '/usr/bin/php',
        '/usr/iports/php{major}{minor}/bin/php',
        '/usr/lib/cgi-bin/php{major}.{minor}',
        '/usr/lib64/php{major}.{minor}/bin/php',
        '/usr/local/bin/edis-php-cli-{major}{minor}-stable-openssl',
        '/usr/local/bin/edis-php-cli-{major}{minor}',
        '/usr/local/bin/php_cli',
        '/usr/local/bin/php',
        '/usr/local/bin/php{major}-{major}{minor}LATEST-CLI',
        '/usr/local/bin/php{major}.{minor}.{release}-cli',
        '/usr/local/php-{major}.{minor}/bin/php',
        '/usr/local/php{major}{minor}/bin/php',
        '/usr/local/phpfarm/inst/php-{major}.{minor}/bin/php',
        '/usr/local/php{major}{minor}/bin/php',
        '/opt/phpbrew/php/php-{major}.{minor}/bin/php',
        '/opt/phpfarm/inst/php-{major}.{minor}/bin/php-cgi',
        '/package/host/localhost/php-{major}.{minor}/bin/php',
        '/Applications/MAMP/bin/php/php{major}.{minor}.{release}/bin/php',
        'C:\XAMPP\php\php.exe',
        'D:\XAMPP\php\php.exe',
        'C:\MAMP\bin\php\php{major}.{minor}.{release}\php.exe',
        'D:\MAMP\bin\php\php{major}.{minor}.{release}\php.exe',
        'D:\laragon\bin\php\php-{major}.{minor}.{release}-Win32-VC15-x64\php.EXE',
        'C:\laragon\bin\php\php-{major}.{minor}.{release}-Win32-VC15-x64\php.EXE',
    ];

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @var PhpExecutableFinder
     */
    private $phpExecutableFinder;

    public function __construct(PhpExecutableFinder $phpExecutableFinder, ManagerConfig $managerConfig)
    {
        $this->phpExecutableFinder = $phpExecutableFinder;
        $this->managerConfig = $managerConfig;
    }

    /**
     * @return PhpExecutableFinder
     */
    public function getPhpExecutableFinder()
    {
        return $this->phpExecutableFinder;
    }

    /**
     * Gets PHP executable by detecting known server paths.
     *
     * @return string|null
     */
    public function getPhpExecutable()
    {
        $paths = [];

        if ($php_cli = $this->managerConfig->get('php_cli')) {
            $paths[] = $php_cli;
        }

        foreach (self::PHP_BINARIES as $path) {
            $paths[] = $this->getPhpVersionPath($path);
        }

        $found = $this->phpExecutableFinder->find($paths);

        if ($php_cli && $found !== $php_cli) {
            $this->managerConfig->set('php_cli', $found);
        }

        return $found;
    }

    /**
     * Gets environment variables for the PHP command line process.
     *
     * @return array
     */
    public function getPhpEnv()
    {
        $env = array_map(function () { return false; }, $_ENV);
        $env['PATH'] = $_ENV['PATH'] ?? false;
        $env['PHP_PATH'] = $this->getPhpExecutable();

        return $env;
    }

    /**
     * Returns the background process forker classes for the current server.
     *
     * @return array
     */
    public function getProcessForkers()
    {
        if (self::PLATFORM_WINDOWS === $this->getPlatform()) {
            return [WindowsStartForker::class];
        }

        return [DisownForker::class, NohupForker::class];
    }

    /**
     * Returns the server platform (Windows or UNIX).
     *
     * @return string
     */
    public function getPlatform()
    {
        return '\\' === \DIRECTORY_SEPARATOR ? self::PLATFORM_WINDOWS : self::PLATFORM_UNIX;
    }

    /**
     * Gets versionised path to PHP binary.
     *
     * @param string $path
     *
     * @return string
     */
    private function getPhpVersionPath($path)
    {
        return str_replace(
            [
                '{major}',
                '{minor}',
                '{release}',
                '{extra}',
            ],
            [
                PHP_MAJOR_VERSION,
                PHP_MINOR_VERSION,
                PHP_RELEASE_VERSION,
                PHP_EXTRA_VERSION,
            ],
            $path
        );
    }
}
