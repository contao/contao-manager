<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
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
     * Constructor.
     *
     * @param string     $environment
     * @param ApiKernel  $kernel
     * @param TaskConfig $taskConfig
     * @param Filesystem $filesystem
     */
    public function __construct($environment, ApiKernel $kernel, TaskConfig $taskConfig, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->environment = $environment;
        $this->kernel = $kernel;

        parent::__construct($taskConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun()
    {
        $this->filesystem->remove($this->getCacheDir());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Deleting cache directory …');
        $status->setDetail($this->getCacheDir());

        $this->addConsoleStatus($status);
    }

    protected function getName()
    {
        return 'remove-cache@'.$this->getCacheDir();
    }

    /**
     * Gets the Contao cache directory for current environment.
     *
     * @return string
     */
    private function getCacheDir()
    {
        return $this->kernel->getContaoDir().'/var/cache/'.$this->environment;
    }
}
