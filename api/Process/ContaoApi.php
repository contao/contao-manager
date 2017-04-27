<?php

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
