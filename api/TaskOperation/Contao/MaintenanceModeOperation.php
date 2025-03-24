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
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_UPDATE')]
class MaintenanceModeOperation extends AbstractInlineOperation
{
    public function __construct(
        TaskConfig $taskConfig,
        private readonly ConsoleProcessFactory $processFactory,
        private readonly string $state,
    ) {
        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return 'vendor/bin/contao-console contao:maintenance-mode '.$this->state;
    }

    public function continueOnError(): bool
    {
        return true;
    }

    protected function getName(): string
    {
        return 'maintenance-'.$this->state;
    }

    protected function doRun(): bool
    {
        $process = $this->processFactory->createContaoConsoleProcess([
            'contao:maintenance-mode',
            $this->state,
            '--no-interaction',
        ]);

        $process->run();

        return $process->isSuccessful();
    }
}
