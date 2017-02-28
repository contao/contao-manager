<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside;

use Tenside\CoreBundle\Util\InstallationStatusDeterminator as BaseInstallationStatusDeterminator;

class InstallationStatusDeterminator extends BaseInstallationStatusDeterminator
{
    private $home;
    private $isConfigured;

    /**
     * {@inheritdoc}
     */
    public function __construct(HomePathDeterminator $homePathDeterminator)
    {
        parent::__construct($homePathDeterminator);

        $this->home = $homePathDeterminator;
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
            $this->home->tensideDataDir().DIRECTORY_SEPARATOR.'config.json'
        );
    }
}
