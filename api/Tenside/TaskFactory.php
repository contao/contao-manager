<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Tenside\Task\RebuildCacheTask;
use Contao\ManagerApi\Tenside\Task\SelfUpdateTask;
use Tenside\Core\Task\TaskFactoryInterface;
use Tenside\Core\Util\JsonArray;

/**
 * This class is the factory for all app bundle tasks.
 */
class TaskFactory implements TaskFactoryInterface
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
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ConsoleProcessFactory $processFactory
     */
    public function __construct(ApiKernel $kernel, ConsoleProcessFactory $processFactory)
    {
        $this->kernel = $kernel;
        $this->processFactory = $processFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported($taskType)
    {
        return in_array($taskType, ['rebuild-cache', 'self-update'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($taskType, JsonArray $metaData)
    {
        switch ($taskType) {
            case 'rebuild-cache':
                return new RebuildCacheTask($this->kernel, $this->processFactory, $metaData);

            case 'self-update':
                return new SelfUpdateTask($this->processFactory, $metaData);
        }

        throw new \InvalidArgumentException(sprintf('Unsupported task type "%s"', $taskType));
    }
}
