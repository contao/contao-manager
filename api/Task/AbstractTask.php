<?php

namespace Contao\ManagerApi\Task;

use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\ProcessController;

abstract class AbstractTask implements TaskInterface
{
    private static $signals = [
        1 => 'SIGHUP',
        2 => 'SIGINT',
        3 => 'SIGQUIT',
        15 => 'SIGTERM',
        9 => 'SIGKILL',
    ];

    /**
     * @param Process|ProcessController $process
     *
     * @return string
     */
    protected function getProcessError($process)
    {
        $output = '';

        if ($process->isTerminated() && ($exitCode = $process->getExitCode()) > 0) {
            $output .= sprintf(
                "\n\nProcess terminated with exit code %s\nReason: %s",
                $exitCode,
                $process->getExitCodeText()
            );

            if ($process->hasBeenSignaled()) {
                $output .= $this->getSignalText($process->getTermSignal());
            } elseif ($process->hasBeenStopped()) {
                $output .= $this->getSignalText($process->getStopSignal());
            }
        }

        return $output;
    }

    /**
     * @param int $signal
     *
     * @return string
     */
    private function getSignalText($signal)
    {
        if (isset(static::$signals[$signal])) {
            return sprintf(' [%s]', static::$signals[$signal]);
        }

        return sprintf(' [signal %s]', $signal);
    }
}
