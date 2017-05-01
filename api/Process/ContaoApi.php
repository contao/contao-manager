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
     * @var ConsoleProcessFactory
     */
    private $console;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $console
     */
    public function __construct(ConsoleProcessFactory $console)
    {
        $this->console = $console;
    }

    public function getContaoVersion($throwException = false)
    {
        $process = $this->console->createContaoConsoleProcess(['contao:version']);

        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        if ($throwException) {
            throw new \RuntimeException('Could not find Contao version');
        }

        return null;
    }
}
