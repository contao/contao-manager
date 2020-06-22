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

class InlineForker extends AbstractForker
{
    public function run(string $configFile): void
    {
        $arguments = $this->command;
        $binary = array_shift($arguments);

        $commandline = sprintf(
            '%s %s %s',
            escapeshellcmd($binary),
            implode(' ', array_map([$this, 'escapeArgument'], $arguments)),
            $this->escapeArgument($configFile)
        );

        $process = $this->startCommand($commandline);
        $process->wait();
    }

    public function isSupported(): bool
    {
        return true;
    }
}
