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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INSTALL')]
class RequireOperation extends AbstractProcessOperation
{
    public function __construct(
        ConsoleProcessFactory $processFactory,
        private readonly array $required,
    ) {
        try {
            $process = $processFactory->restoreBackgroundProcess('composer-require');

            parent::__construct($process);
        } catch (\Exception) {
            $arguments = array_merge(
                [
                    'composer',
                    'require',
                ],
                $this->required,
                [
                    '--no-update',
                    '--no-scripts',
                    '--prefer-stable',
                    '--sort-packages',
                    '--no-ansi',
                    '--no-interaction',
                ],
            );

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-require',
            );

            parent::__construct($process);
        }
    }

    public function getSummary(): string
    {
        return 'composer require '.implode(' ', $this->required).' --no-update';
    }
}
