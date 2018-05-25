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
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class InstallOperation extends AbstractProcessOperation
{
    /**
     * @var TaskConfig
     */
    private $taskConfig;

    /**
     * @var null
     */
    private $timeout;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param TaskConfig            $taskConfig
     * @param bool                  $dryRun
     * @param null                  $timeout
     */
    public function __construct(ConsoleProcessFactory $processFactory, TaskConfig $taskConfig, $dryRun = false, $timeout = null)
    {
        $this->taskConfig = $taskConfig;
        $this->timeout = $timeout;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-install');
            $retries = $taskConfig->getState('install-timeout', 0);

            if (null !== $timeout && $process->isTimedOut() && $retries < 4) {
                $taskConfig->setState('install-timeout', ++$retries);

                throw new \RuntimeException('Process timed out, restarting');
            }

            parent::__construct($process);
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

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-install'
            );

            if (null !== $timeout) {
                $process->setTimeout($timeout - 5);
            }

            parent::__construct($process);
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        if (null !== $this->timeout && ($attempt = $this->taskConfig->getState('install-timeout', 0)) > 0) {
            $status->setSummary(sprintf('Installing Composer dependencies (retrying %s/5)…', $attempt+1));
        } else {
            $status->setSummary('Installing Composer dependencies …');
        }

        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
