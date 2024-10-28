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
    public function __construct(
        ConsoleProcessFactory $processFactory,
        private readonly TaskConfig $taskConfig,
        Environment $environment,
        private readonly Translator $translator,
        private readonly bool $dryRun = false,
        bool $retry = true,
    ) {
        try {
            $process = $processFactory->restoreBackgroundProcess('composer-install');
            $retries = $this->taskConfig->getState('install-retry', 0);

            if ($retry && $retries < 4 && $process->isTerminated() && !$process->isSuccessful()) {
                $process->delete();
                $this->taskConfig->setState('install-retry', ++$retries);

                throw new \RuntimeException('Install process failed, restarting');
            }

            parent::__construct($process);
        } catch (\Exception) {
            $arguments = [
                'composer',
                'install',
                '--no-dev',
                '--no-progress',
                '--no-ansi',
                '--no-interaction',
                '--optimize-autoloader',
            ];

            if ($this->dryRun) {
                $arguments[] = '--dry-run';
                $arguments[] = '--no-scripts';
                $arguments[] = '--no-plugins';
            }

            if ($environment->isDebug()) {
                $arguments[] = '--profile';
                $arguments[] = '-vvv';
            }

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-install',
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

    public function getDetails(): string|null
    {
        if (!$this->isStarted()) {
            return null;
        }

        if ($this->isRunning() && ($attempt = $this->taskConfig->getState('install-retry', 0)) > 0) {
            return $this->translator->trans(
                'taskoperation.composer-install.retry',
                ['current' => $attempt + 1, 'max' => 5],
            );
        }

        if ($this->isSuccessful()) {
            $output = $this->process->getOutput();

            if (str_contains($output, 'Nothing to install or update')) {
                return $this->translator->trans('taskoperation.composer-install.nothing');
            }

            $operations = $this->getPackageOperations($output);

            if (null !== $operations) {
                return $this->translator->trans('taskoperation.composer-install.result', $operations);
            }
        }

        return '';
    }

    private function getPackageOperations(string $output): array|null
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
