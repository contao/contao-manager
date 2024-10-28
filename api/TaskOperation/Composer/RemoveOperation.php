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

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class RemoveOperation extends AbstractProcessOperation
{
    public function __construct(
        ConsoleProcessFactory $processFactory,
        private readonly array $removed,
    ) {
        try {
            $process = $processFactory->restoreBackgroundProcess('composer-remove');

            parent::__construct($process);
        } catch (\Exception) {
            $arguments = array_merge(
                [
                    'composer',
                    'remove',
                ],
                $this->removed,
                [
                    '--no-update',
                    '--no-scripts',
                    '--no-ansi',
                    '--no-interaction',
                ],
            );

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-remove',
            );

            parent::__construct($process);
        }
    }

    public function getSummary(): string
    {
        return 'composer remove '.implode(' ', $this->removed);
    }
}
