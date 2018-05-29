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

class RequireOperation extends AbstractProcessOperation
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
     * @param array                 $required
     */
    public function __construct(ConsoleProcessFactory $processFactory, TaskConfig $taskConfig, array $required)
    {
        $this->taskConfig = $taskConfig;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-require');

            parent::__construct($process);
        } catch (\Exception $e) {
            $arguments = array_merge(
                [
                    'composer',
                    'require',
                ],
                $required,
                [
                    '--no-progress',
                    '--no-suggest',
                    '--no-update',
                    '--no-scripts',
                    '--prefer-stable',
                    '--sort-packages',
                    '--no-ansi',
                    '--no-interaction',
                ]
            );

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-require'
            );

            parent::__construct($process);
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Adding Composer packages …');

        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
