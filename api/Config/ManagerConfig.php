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
use Contao\ManagerApi\Process\ServerInfo;
use Symfony\Component\Filesystem\Filesystem;

class ManagerConfig extends AbstractConfig
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * Constructor.
     *
     * @param ApiKernel  $kernel
     * @param ServerInfo $serverInfo
     * @param Filesystem $filesystem
     */
    public function __construct(ApiKernel $kernel, ServerInfo $serverInfo, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'manager.json';

        parent::__construct($configFile, $filesystem);

        $this->kernel = $kernel;
        $this->serverInfo = $serverInfo;
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

        return $this->serverInfo->getPhpExecutable();
    }
}
