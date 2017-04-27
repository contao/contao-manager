<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Process;

class ContaoApi
{
    /**
     * @var CommandLine
     */
    private $commandLine;

    /**
     * Constructor.
     *
     * @param CommandLine $commandLine
     */
    public function __construct(CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    public function getContaoVersion($throwException = false)
    {
        $process = $this->commandLine->runContaoConsole(['contao:version']);

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        if ($throwException) {
            throw new \RuntimeException('Could not find Contao version');
        }

        return null;
    }
}
