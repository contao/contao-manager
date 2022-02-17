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

class MaintenanceModeOperation extends AbstractInlineOperation
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var string
     */
    private $state;

    public function __construct(TaskConfig $taskConfig, ConsoleProcessFactory $processFactory, string $state)
    {
        $this->processFactory = $processFactory;
        $this->state = $state;

        parent::__construct($taskConfig);
    }

    public function getName(): string
    {
        return 'maintenance-'.$this->state;
    }

    public function getSummary(): string
    {
        return 'vendor/bin/contao-console contao:maintenance-mode '.$this->state;
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
