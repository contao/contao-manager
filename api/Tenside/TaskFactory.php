<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside;

use Contao\ManagerApi\Tenside\Task\RebuildCacheTask;
use Tenside\Core\Config\TensideJsonConfig;
use Tenside\Core\Task\TaskFactoryInterface;
use Tenside\Core\Util\JsonArray;

/**
 * This class is the factory for all app bundle tasks.
 */
class TaskFactory implements TaskFactoryInterface
{
    /**
     * The home path.
     *
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * The configuration in use.
     *
     * @var TensideJsonConfig
     */
    private $config;

    /**
     * Constructor.
     *
     * @param HomePathDeterminator $home
     * @param TensideJsonConfig    $config
     */
    public function __construct(HomePathDeterminator $home, TensideJsonConfig $config)
    {
        $this->home = $home;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported($taskType)
    {
        return 'rebuild-cache' === $taskType;
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($taskType, JsonArray $metaData)
    {
        if (!$this->isTypeSupported($taskType)) {
            throw new \InvalidArgumentException(sprintf('Unsupported task type "%s"', $taskType));
        }

        return new RebuildCacheTask($this->home, $this->config, $metaData);
    }
}
