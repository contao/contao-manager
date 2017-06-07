<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Process\PhpExecutableFinder;
use Symfony\Component\Filesystem\Filesystem;

class ManagerConfig extends AbstractConfig
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * Constructor.
     *
     * @param ApiKernel  $kernel
     * @param Filesystem $filesystem
     */
    public function __construct(ApiKernel $kernel, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'manager.json';

        parent::__construct($configFile, $filesystem);

        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        $data = parent::all();

        $data['secret'] = $this->getSecret();
        $data['php_cli'] = $this->getPhpExecutable();

        return $data;
    }

    /**
     * Gets the application secret.
     *
     * @return string
     */
    public function getSecret()
    {
        if (!isset($this->data['secret'])) {
            $this->setSecret(bin2hex(random_bytes(40)));
        }

        return $this->data['secret'];
    }

    /**
     * Sets the application secret.
     *
     * @param string $secret
     */
    public function setSecret($secret)
    {
        if (empty($secret)) {
            throw new \InvalidArgumentException('Secret cannot be empty.');
        }

        $this->data['secret'] = (string) $secret;

        $this->save();
    }

    /**
     * Gets the PHP executable from config or PhpExecutableFinder.
     *
     * @return string
     */
    public function getPhpExecutable()
    {
        if (isset($this->data['php_cli'])) {
            return $this->data['php_cli'];
        }

        $paths = [];
        $server = $this->kernel->getServerInfo();

        if (isset($server['provider']['php'])) {
            foreach ($server['provider']['php'] as $path) {
                $paths[] = str_replace(['{major}', '{minor}'], [PHP_MAJOR_VERSION, PHP_MINOR_VERSION], $path);
            }
        }

        return (new PhpExecutableFinder())->find($paths);
    }
}
