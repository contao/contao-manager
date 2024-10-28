<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class RemoveVendorOperation extends AbstractInlineOperation
{
    public function __construct(TaskConfig $taskConfig, private readonly Environment $environment, private readonly Filesystem $filesystem)
    {
        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return 'rm -rf vendor';
    }

    protected function doRun(): bool
    {
        $this->filesystem->remove($this->environment->getVendorDir());

        return true;
    }

    protected function getName(): string
    {
        return 'remove-vendor';
    }
}
