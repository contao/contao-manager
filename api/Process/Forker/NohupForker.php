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

class NohupForker extends AbstractForker
{
    public function run(string $configFile): void
    {
        $commandline = \sprintf(
            'exec nohup %s %s >/dev/null </dev/null 2>&1 &',
            implode(' ', array_map([$this, 'escapeArgument'], $this->command)),
            $this->escapeArgument($configFile),
        );

        $this->startCommand($commandline);
    }

    public function isSupported(): bool
    {
        try {
            Process::fromShellCommandline('exec nohup ls')->mustRun(null, $this->env);
        } catch (ProcessFailedException) {
            return false;
        }

        return true;
    }
}
