<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Process\Forker;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class WindowsStartForker extends AbstractForker
{
    public function run(string $configFile): void
    {
        $commandline = sprintf(
            'start /b %s %s 2>&1 >nul <nul',
            implode(' ', array_map([$this, 'escapeArgument'], $this->command)),
            $this->escapeArgument($configFile)
        );

        $this->startCommand($commandline);
    }

    public function isSupported(): bool
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            return false;
        }

        try {
            Process::fromShellCommandline('start /b dir')->mustRun(null, $this->env);
        } catch (ProcessFailedException $e) {
            return false;
        }

        return true;
    }
}
