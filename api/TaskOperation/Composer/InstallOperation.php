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
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class InstallOperation extends AbstractProcessOperation
{
    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param bool                  $dryRun
     */
    public function __construct(ConsoleProcessFactory $processFactory, $dryRun = false)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('composer-install'));
        } catch (\Exception $e) {
            $arguments = [
                'composer',
                'install',
                '--prefer-dist',
                '--no-dev',
                '--no-progress',
                '--no-suggest',
                '--no-interaction',
                '--optimize-autoloader',
            ];

            if ($dryRun) {
                $arguments[] = '--dry-run';
            }

            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    $arguments,
                    'composer-install'
                )
            );
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Installing Composer dependencies …');
        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
