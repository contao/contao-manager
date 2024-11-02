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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_UPDATE')]
class BackupCreateOperation extends AbstractProcessOperation
{
    public function __construct(ConsoleProcessFactory $processFactory, string $processId = 'backup-create')
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess($processId));
        } catch (\Exception) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['contao:backup:create'], $processId));
        }
    }

    public function getSummary(): string
    {
        return 'vendor/bin/contao-console contao:backup:create';
    }
}
