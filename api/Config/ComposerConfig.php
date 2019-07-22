<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Filesystem\Filesystem;

class ComposerConfig extends AbstractConfig
{
    public function __construct(ApiKernel $kernel, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getConfigDir().\DIRECTORY_SEPARATOR.'config.json';

        parent::__construct($configFile, $filesystem);
    }
}
