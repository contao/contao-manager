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
use Symfony\Component\Filesystem\Filesystem;

class ComposerConfig extends AbstractConfig
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
        $configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'config.json';

        parent::__construct($configFile, $filesystem);

        $this->kernel = $kernel;
    }

    /**
     * Initializes the default values for the Composer configuration.
     */
    public function initialize()
    {
        if (0 !== $this->count()) {
            return;
        }

        $this->data['config']['preferred-install'] = 'dist';
        $this->data['config']['store-auths'] = false;
        $this->data['config']['cache-files-ttl'] = 0;
        $this->data['config']['optimize-autoloader'] = true;
        $this->data['config']['sort-packages'] = true;
        $this->data['config']['discard-changes'] = true;

        $this->save();
    }
}
