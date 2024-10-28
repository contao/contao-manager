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

class ClearCacheOperation extends AbstractProcessOperation
{
    public function __construct(ConsoleProcessFactory $processFactory)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('clear-cache'));
        } catch (\Exception) {
            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    [
                        'composer',
                        'clear-cache',
                        '--no-interaction',
                    ],
                    'clear-cache',
                ),
            );
        }
    }

    public function getSummary(): string
    {
        return 'composer clear-cache';
    }
}
