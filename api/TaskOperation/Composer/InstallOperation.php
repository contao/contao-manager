<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

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
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param TaskConfig            $taskConfig
     * @param Translator            $translator
     * @param bool                  $dryRun
     * @param null                  $timeout
     */
    public function __construct(ConsoleProcessFactory $processFactory, TaskConfig $taskConfig, Translator $translator, $dryRun = false, $timeout = null)
    {
        $this->taskConfig = $taskConfig;
        $this->translator = $translator;
        $this->timeout = $timeout;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-install');
            $retries = $taskConfig->getState('install-retry', 0);

            if ($process->isTerminated() && !$process->isSuccessful() && $retries < 4) {
                $taskConfig->setState('install-retry', ++$retries);

                throw new \RuntimeException('Install process failed, restarting');
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
                '--no-ansi',
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
        if (($attempt = $this->taskConfig->getState('install-retry', 0)) > 0) {
            $status->setSummary(
                $this->translator->trans(
                    'taskoperation.composer-install.summaryRetry',
                    ['current' => $attempt + 1, 'max' => 5])
            );
        } else {
            $status->setSummary($this->translator->trans('taskoperation.composer-install.summary'));
        }

        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
