<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside\Task;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Symfony\Component\Filesystem\Filesystem;
use Tenside\Core\Task\AbstractCliSpawningTask;
use Tenside\Core\Util\JsonArray;

/**
 * This class runs the cache clear command.
 */
class RebuildCacheTask extends AbstractCliSpawningTask
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * {@inheritdoc}
     */
    public function __construct(ApiKernel $kernel, ConsoleProcessFactory $processFactory, JsonArray $file)
    {
        parent::__construct($file);

        $this->kernel = $kernel;
        $this->processFactory = $processFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function doPerform()
    {
        $environment = $this->file->get('environment') ?: 'prod';

        $this->deleteCacheDirectory($environment);
        $this->runSymfonyCommand('cache:clear', ['--no-warmup'], $environment);
        $this->runSymfonyCommand('cache:warmup', [], $environment);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'rebuild-cache';
    }

    /**
     * Manually removes the cache directory.
     *
     * @param string $environment
     */
    private function deleteCacheDirectory($environment)
    {
        (new Filesystem())->remove($this->kernel->getContaoDir().'/var/cache/'.$environment);
    }

    /**
     * Runs a Symfony command.
     *
     * @param string $command
     * @param array  $arguments
     * @param string $environment
     */
    private function runSymfonyCommand($command, array $arguments = [], $environment = 'prod')
    {
        $process = $this->processFactory->createContaoConsoleProcess(
            array_merge([$command, '--env='.$environment], $arguments)
        );

        $this->runProcess($process);
    }
}
