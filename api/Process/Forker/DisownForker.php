<?php

namespace Contao\ManagerApi\Process\Forker;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DisownForker extends AbstractForker
{
    /**
     * {@inheritdoc}
     */
    public function run($configFile)
    {
        $commandline = sprintf(
            'exec %s %s >/dev/null 2>&1 </dev/null & disown',
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
            (new Process("exec echo '' & disown"))->mustRun(null, $this->env);
        } catch (ProcessFailedException $e) {
            return false;
        }

        return true;
    }
}
