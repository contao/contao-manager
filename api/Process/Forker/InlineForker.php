<?php

namespace Contao\ManagerApi\Process\Forker;

class InlineForker extends AbstractForker
{

    /**
     * Executes a command.
     *
     * @param string $configFile
     */
    public function run($configFile)
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
     * Returns whether this forker is supported on the current platform.
     *
     * @return bool
     */
    public function isSupported()
    {
        return true;
    }
}
