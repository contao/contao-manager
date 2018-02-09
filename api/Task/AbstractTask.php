<?php

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\I18n\Translator;
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
     * @param TaskStatus $status
     * @param Translator $translator
     */
    protected function setStatusLabels(TaskStatus $status, Translator $translator)
    {
        switch ($status->getStatus()) {
            case TaskStatus::STATUS_ACTIVE:
                break;

            case TaskStatus::STATUS_COMPLETE:
                $status->setSummary('Console task complete!');
                $status->setDetail('The background task was completed successfully. Check the console protocol for the details.');
                break;

            case TaskStatus::STATUS_STOPPED:
                $status->setSummary('Console task terminated!');
                $status->setDetail('The background task was cancelled. Please check the console protocol.');
                break;

            case TaskStatus::STATUS_ERROR:
                $status->setSummary('Console task terminated!');
                $status->setDetail('The background task has stopped unexpectedly. Please check the console protocol.');
                break;
        }
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
