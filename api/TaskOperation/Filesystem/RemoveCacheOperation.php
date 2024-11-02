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

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_UPDATE')]
class RemoveCacheOperation extends AbstractInlineOperation
{
    public function __construct(
        private readonly string $environment,
        private readonly ApiKernel $kernel,
        TaskConfig $taskConfig,
        private readonly Filesystem $filesystem,
        private readonly string $name = 'remove-cache',
    ) {
        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return 'rm -rf var/cache/'.$this->environment;
    }

    protected function doRun(): bool
    {
        $this->filesystem->remove($this->getCacheDir());

        return true;
    }

    protected function getName(): string
    {
        return $this->name.'@'.$this->getCacheDir();
    }

    /**
     * Gets the Contao cache directory for current environment.
     */
    private function getCacheDir(): string
    {
        return $this->kernel->getProjectDir().'/var/cache/'.$this->environment;
    }
}
