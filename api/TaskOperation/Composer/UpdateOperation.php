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
    /**
     * @var array
     */
    private $packages;

    /**
     * @var bool
     */
    private $dryRun;

    public function __construct(ConsoleProcessFactory $processFactory, Environment $environment, array $packages = [], bool $dryRun = false)
    {
        $this->packages = $packages;
        $this->dryRun = $dryRun;

        try {
            parent::__construct($processFactory->restoreBackgroundProcess('composer-update'));
        } catch (\Exception $e) {
            $arguments = array_merge(
                [
                    'composer',
                    'update',
                ],
                $packages,
                [
                    '--with-dependencies',
                    '--prefer-dist',
                    '--no-dev',
                    '--no-progress',
                    '--no-ansi',
                    '--no-interaction',
                    '--optimize-autoloader',
                ]
            );

            if ($dryRun) {
                $arguments[] = '--dry-run';
            }

            if ($environment->isDebug()) {
                $arguments[] = '--profile';
                $arguments[] = '-vvv';
            }

            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    $arguments,
                    'composer-update'
                )
            );
        }
    }

    public function getSummary(): string
    {
        $summary = 'composer update';

        if (!empty($this->packages)) {
            $summary .= ' '.implode(' ', $this->packages);
        }

        if ($this->dryRun) {
            $summary .= ' --dry-run';
        }

        return $summary;
    }
}
