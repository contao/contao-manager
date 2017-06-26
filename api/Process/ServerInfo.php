<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Process;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ServerInfo
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var string
     */
    private $providerFile;

    /**
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param ApiKernel $kernel
     * @param string    $providerFile
     */
    public function __construct(ApiKernel $kernel, $providerFile)
    {
        $this->kernel = $kernel;
        $this->providerFile = $providerFile;
    }

    /**
     * Gets all available server info.
     *
     * @return array
     */
    public function getData()
    {
        $this->collect();

        return $this->data;
    }

    /**
     * Gets PHP executable from manager.json or by detecting known server paths.
     *
     * @return string|null
     */
    public function getPhpExecutable()
    {
        $this->collect();

        $paths = [];

        if (isset($this->data['provider']['php'])) {
            foreach ($this->data['provider']['php'] as $path => $arguments) {
                $paths[] = $this->getPhpVersionPath($path);
            }
        }

        return (new PhpExecutableFinder())->find($paths);
    }

    /**
     * Gets arguments for known PHP executable paths.
     *
     * @param string|null $executable
     *
     * @return array
     */
    public function getPhpArguments($executable = null)
    {
        $this->collect();

        if (null === $executable) {
            $executable = $this->getPhpExecutable();
        }

        if ($executable && isset($this->data['provider']['php'])) {
            foreach ($this->data['provider']['php'] as $path => $arguments) {
                if ($this->getPhpVersionPath($path) === $executable) {
                    return $arguments;
                }
            }
        }

        return [];
    }

    /**
     * Collects information about current server.
     */
    private function collect()
    {
        if (null !== $this->data) {
            return;
        }

        $ipInfo = $this->getIpInfo();
        $provider = $this->getProviderConfig($ipInfo['hostname']);
        $version = $this->getManagerVersion();

        $this->data = [
            'app' => [
                'version' => $version,
                'env' => $this->kernel->getEnvironment(),
                'debug' => $this->kernel->isDebug(),
                'cache_dir' => $this->kernel->getCacheDir(),
                'log_dir' => $this->kernel->getLogDir(),
                'contao_dir' => $this->kernel->getContaoDir(),
                'manager_dir' => $this->kernel->getManagerDir(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'version_id' => PHP_VERSION_ID,
                'sapi' => PHP_SAPI,
                'locale' => class_exists('Locale', false) && \Locale::getDefault() ? \Locale::getDefault() : '',
                'timezone' => date_default_timezone_get(),
            ],
            'server' => array_merge($ipInfo, [
                'os_name' => php_uname('s'),
                'os_version' => php_uname('r'),
                'arch' => PHP_INT_SIZE * 8,
            ]),
            'provider' => $provider,
        ];

        if ($this->data['server']['os_name'] === $this->data['server']['os_version']) {
            $this->data['server']['os_version'] = '';
        }
    }

    private function getManagerVersion()
    {
        $version = $this->kernel->getVersion();

        if ($version === ('@'.'package_version'.'@')) {
            $git = new Process('git describe --always');

            try {
                $git->mustRun();
                $version = trim($git->getOutput());
            } catch (ProcessFailedException $e) {
                return 'n/a';
            }
        }

        return $version;
    }

    /**
     * Resolves IP information of the current server.
     *
     * @return array
     */
    private function getIpInfo()
    {
        $template = [
            'ip' => '',
            'hostname' => '',
            'city' => '',
            'region' => '',
            'country' => '',
            'loc' => '',
            'org' => '',
        ];

        /** @noinspection UsageOfSilenceOperatorInspection */
        $data = @file_get_contents('https://ipinfo.io/json') ?: @file_get_contents('http://ipinfo.io/json');

        if (!empty($data)) {
            return array_merge($template, json_decode($data, true));
        }

        /** @noinspection UsageOfSilenceOperatorInspection */
        $template['ip'] = (string) @file_get_contents('https://api.ipify.org') ?: @file_get_contents('http://api.ipify.org');
        /** @noinspection UsageOfSilenceOperatorInspection */
        $template['hostname'] = (string) @gethostbyaddr($template['ip']);

        return $template;
    }

    /**
     * Gets provider config from current hostname or empty array for unknown hosts.
     *
     * @param string $hostname
     *
     * @return array
     */
    private function getProviderConfig($hostname)
    {
        $offset = 0;
        $providers = Yaml::parse(file_get_contents($this->providerFile));

        while ($dot = strpos($hostname, '.', $offset)) {
            if (isset($providers[substr($hostname, $offset)])) {
                return $providers[substr($hostname, $offset)];
            }

            $offset = $dot + 1;
        }

        return [];
    }

    /**
     * Gets versionized path to PHP binary.
     *
     * @param string $path
     *
     * @return string
     */
    private function getPhpVersionPath($path)
    {
        return str_replace(['{major}', '{minor}'], [PHP_MAJOR_VERSION, PHP_MINOR_VERSION], $path);
    }
}
