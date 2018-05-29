<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class RemoveOperation extends AbstractProcessOperation
{
    /**
     * @var TaskConfig
     */
    private $taskConfig;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param TaskConfig            $taskConfig
     * @param array                 $removed
     */
    public function __construct(ConsoleProcessFactory $processFactory, TaskConfig $taskConfig, array $removed)
    {
        $this->taskConfig = $taskConfig;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-remove');

            parent::__construct($process);
        } catch (\Exception $e) {
            $arguments = array_merge(
                [
                    'composer',
                    'remove',
                ],
                $removed,
                [
                    '--no-progress',
                    '--no-update',
                    '--no-scripts',
                    '--no-ansi',
                    '--no-interaction',
                ]
            );

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-remove'
            );

            parent::__construct($process);
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Removing Composer packages …');

        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
