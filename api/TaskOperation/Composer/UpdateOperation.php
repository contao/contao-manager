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
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class UpdateOperation extends AbstractProcessOperation
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var array
     */
    private $packages;

    /**
     * Constructor.
     *
     * @param bool $dryRun
     */
    public function __construct(ConsoleProcessFactory $processFactory, Environment $environment, Translator $translator, array $packages = [], $dryRun = false)
    {
        $this->translator = $translator;
        $this->packages = $packages;

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
        return 'composer update '.implode(' ', $this->packages);
    }
}
