<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class InstallUnlockOperation extends AbstractProcessOperation
{
    public function __construct(ConsoleProcessFactory $processFactory, string $processId = 'install-unlock')
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess($processId));
        } catch (\Exception $e) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['contao:install:unlock'], $processId));
        }
    }

    public function getSummary(): string
    {
        return 'vendor/bin/contao-console contao:install:unlock';
    }
}
