<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Composer\Environment;
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
    public function __construct(ConsoleProcessFactory $processFactory, TaskConfig $taskConfig, Environment $environment, Translator $translator, $dryRun = false, $timeout = null)
    {
        $this->taskConfig = $taskConfig;
        $this->translator = $translator;

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

            if ($environment->isDebug()) {
                $arguments[] = '--profile';
                $arguments[] = '-vvv';
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
        if (!$this->process->isStarted()) {
            return;
        }

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
