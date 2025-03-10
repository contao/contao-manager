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

class UploadsConfig extends AbstractConfig
{
    public function __construct(ApiKernel $kernel, Filesystem $filesystem)
    {
        parent::__construct(
            $kernel->getConfigDir().\DIRECTORY_SEPARATOR.'uploads.json',
            $filesystem,
            $kernel->getTranslator(),
        );
    }
}
