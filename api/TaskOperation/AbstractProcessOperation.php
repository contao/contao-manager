<?php

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Task\TaskStatus;
use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\ProcessController;

abstract class AbstractProcessOperation implements TaskOperationInterface
{
    private static $signals = [
        1 => 'SIGHUP',
        2 => 'SIGINT',
        3 => 'SIGQUIT',
        15 => 'SIGTERM',
        9 => 'SIGKILL',
    ];

    /**
     * @var Process|ProcessController
     */
    protected $process;

    /**
     * Constructor.
     *
     * @param Process|ProcessController $process
     */
    public function __construct($process)
    {
        $this->process = $process;
    }

    public function isStarted()
    {
        return $this->process->isStarted();
    }

    public function isRunning()
    {
        return $this->process->isRunning();
    }

    public function isSuccessful()
    {
        return $this->process->isSuccessful();
    }

    public function isCancellable()
    {
        return true;
    }

    public function run()
    {
        if (!$this->process->isStarted()) {
            $this->process->start();
        }
    }

    public function abort()
    {
        $this->process->stop();

        return $this->process->isRunning();
    }

    public function delete()
    {
        $this->process->delete();
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->addConsole(
            $this->process->getOutput().$this->process->getErrorOutput().$this->getProcessError(),
            $this->process->getCommandLine()
        );
    }

    /**
     * @return string
     */
    protected function getProcessError()
    {
        $output = '';

        if ($this->process->isTerminated() && ($exitCode = $this->process->getExitCode()) > 0) {
            $output .= sprintf(
                "\n\nProcess terminated with exit code %s\nReason: %s",
                $exitCode,
                $this->process->getExitCodeText()
            );

            if ($this->process->hasBeenSignaled()) {
                $output .= $this->getSignalText($this->process->getTermSignal());
            } elseif ($this->process->hasBeenStopped()) {
                $output .= $this->getSignalText($this->process->getStopSignal());
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
