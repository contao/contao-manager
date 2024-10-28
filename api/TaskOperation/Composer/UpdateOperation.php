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
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class UpdateOperation extends AbstractProcessOperation
{
    public function __construct(
        ConsoleProcessFactory $processFactory,
        Environment $environment,
        private readonly array $packages = [],
        private readonly bool $dryRun = false,
    ) {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('composer-update'));
        } catch (\Exception) {
            $arguments = array_merge(
                [
                    'composer',
                    'update',
                ],
                $this->packages,
                [
                    '--with-dependencies',
                    '--no-install',
                    '--no-scripts',
                    '--no-dev',
                    '--no-progress',
                    '--no-ansi',
                    '--no-interaction',
                    '--optimize-autoloader',
                ],
            );

            if ($this->dryRun) {
                $arguments[] = '--dry-run';
                $arguments[] = '--no-scripts';
                $arguments[] = '--no-plugins';
            }

            if ($environment->isDebug()) {
                $arguments[] = '--profile';
                $arguments[] = '-vvv';
            }

            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    $arguments,
                    'composer-update',
                ),
            );
        }
    }

    public function getSummary(): string
    {
        $summary = 'composer update';

        if ([] !== $this->packages) {
            $summary .= ' '.implode(' ', $this->packages);
        }

        $summary .= ' --no-install';

        if ($this->dryRun) {
            $summary .= ' --dry-run';
        }

        return $summary;
    }
}
