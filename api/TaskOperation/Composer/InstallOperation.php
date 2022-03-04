<?php

declare(strict_types=1);

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
     * @var bool
     */
    private $dryRun;

    public function __construct(ConsoleProcessFactory $processFactory, TaskConfig $taskConfig, Environment $environment, Translator $translator, bool $dryRun = false, bool $retry = true)
    {
        $this->taskConfig = $taskConfig;
        $this->translator = $translator;
        $this->dryRun = $dryRun;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-install');
            $retries = $taskConfig->getState('install-retry', 0);

            if ($retry && $process->isTerminated() && !$process->isSuccessful() && $retries < 4) {
                $taskConfig->setState('install-retry', ++$retries);

                throw new \RuntimeException('Install process failed, restarting');
            }

            parent::__construct($process);
        } catch (\Exception $e) {
            $arguments = [
                'composer',
                'install',
                '--no-dev',
                '--no-progress',
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

            // An install task should never need 5min to install
            $process->setTimeout(60 * 5);

            parent::__construct($process);
        }
    }

    public function getSummary(): string
    {
        $summary = 'composer install';

        if ($this->dryRun) {
            $summary .= ' --dry-run';
        }

        return $summary;
    }

    public function getDetails(): ?string
    {
        if (!$this->isStarted()) {
            return null;
        }

        if ($this->isRunning() && ($attempt = $this->taskConfig->getState('install-retry', 0)) > 0) {
            return $this->translator->trans(
                'taskoperation.composer-install.retry',
                ['current' => $attempt + 1, 'max' => 5]
            );
        }

        if ($this->isSuccessful()) {
            $output = $this->process->getOutput();

            if (false !== strpos($output, 'Nothing to install or update')) {
                return $this->translator->trans('taskoperation.composer-install.nothing');
            }

            $operations = $this->getPackageOperations($output);

            if (null !== $operations) {
                return $this->translator->trans('taskoperation.composer-install.result', $operations);
            }
        }

        return '';
    }

    private function getPackageOperations(string $output): ?array
    {
        // Package operations: 6 installs, 85 updates, 0 removals

        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (false !== ($pos = strpos($line, 'Package operations:'))) {
                $operations = substr($line, $pos);

                if (preg_match('{Package operations: (\d+) installs, (\d+) updates, (\d+) removals}', $operations, $match)) {
                    return [
                        'installs' => $match[1],
                        'updates' => $match[2],
                        'removals' => $match[3],
                    ];
                }
            }
        }

        return null;
    }
}
