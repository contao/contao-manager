<?php

namespace Contao\ManagerApi\Process\Forker;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class NohupForker extends AbstractForker
{
    /**
     * {@inheritdoc}
     */
    public function run($configFile)
    {
        $commandline = sprintf(
            'exec nohup %s %s >/dev/null </dev/null 2>&1 &',
            $this->executable,
            escapeshellarg($configFile)
        );

        $this->startCommand($commandline);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported()
    {
        try {
            (new Process('exec nohup ls'))->mustRun(null, $this->env);
        } catch (ProcessFailedException $e) {
            return false;
        }

        return true;
    }
}
