<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside\Task;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Tenside\Core\Task\AbstractCliSpawningTask;
use Tenside\Core\Util\JsonArray;

class SelfUpdateTask extends AbstractCliSpawningTask
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * {@inheritdoc}
     */
    public function __construct(ConsoleProcessFactory $processFactory, JsonArray $file)
    {
        parent::__construct($file);

        $this->processFactory = $processFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function doPerform()
    {
        $process = $this->processFactory->createManagerConsoleProcess(['self-update']);

        $this->runProcess($process);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'self-update';
    }
}
