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

class RemoveCacheOperation extends AbstractInlineOperation
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $environment, ApiKernel $kernel, TaskConfig $taskConfig, Filesystem $filesystem, string $name = 'remove-cache')
    {
        $this->environment = $environment;
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
        $this->name = $name;

        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return 'rm -rf var/cache/'.$this->environment;
    }

    public function doRun(): bool
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
