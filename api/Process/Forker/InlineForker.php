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
    /**
     * {@inheritdoc}
     */
    public function run(string $configFile): void
    {
        $commandline = sprintf(
            '%s %s',
            $this->executable,
            escapeshellarg($configFile)
        );

        $process = $this->startCommand($commandline);
        $process->wait();
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported(): bool
    {
        return true;
    }
}
