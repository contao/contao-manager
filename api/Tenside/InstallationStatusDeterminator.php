<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside;

use Contao\ManagerApi\Config\UserConfig;
use Tenside\CoreBundle\Util\InstallationStatusDeterminator as BaseInstallationStatusDeterminator;

class InstallationStatusDeterminator extends BaseInstallationStatusDeterminator
{
    private $home;
    private $isConfigured;

    /**
     * @var UserConfig
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(UserConfig $config, HomePathDeterminator $homePathDeterminator)
    {
        parent::__construct($homePathDeterminator);

        $this->home = $homePathDeterminator;
        $this->config = $config;
    }

    public function hasUsers()
    {
        return 0 !== $this->config->count();
    }

    /**
     * {@inheritdoc}
     */
    public function isTensideConfigured()
    {
        if (isset($this->isConfigured)) {
            return $this->isConfigured;
        }

        return $this->isConfigured = file_exists(
            $this->home->tensideDataDir().DIRECTORY_SEPARATOR.'manager.json'
        );
    }
}
