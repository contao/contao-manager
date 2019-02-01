<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Process\ProcessController;
use Contao\ManagerApi\Task\TaskStatus;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Process\Process;

abstract class AbstractProcessOperation implements TaskOperationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Process|ProcessController
     */
    protected $process;

    /**
     * @var array
     */
    private static $signals = [
        1 => 'SIGHUP',
        2 => 'SIGINT',
        3 => 'SIGQUIT',
        15 => 'SIGTERM',
        9 => 'SIGKILL',
    ];

    /**
     * Constructor.
     *
     * @param Process|ProcessController $process
     */
    public function __construct($process)
    {
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->process->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return $this->process->isRunning();
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return $this->process->isSuccessful();
    }

    public function hasError()
    {
        return $this->process->isTerminated() && $this->process->getExitCode() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (!$this->process->isStarted()) {
            $this->process->start();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function abort()
    {
        $this->process->stop();

        return $this->process->isRunning();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->process->delete();
    }

    /**
     * Adds the console log to the status console.
     *
     * @param TaskStatus $status
     */
    protected function addConsoleStatus(TaskStatus $status)
    {
        if (!$this->process->isStarted()) {
            return;
        }

        $status->addConsole(
            $this->process->getOutput().$this->process->getErrorOutput().$this->getProcessError(),
            '$ '.$this->process->getCommandLine()
        );
    }

    /**
     * @return string
     */
    protected function getProcessError()
    {
        $output = '';

        if ($this->process->isTerminated()) {
            $signal = '';

            if ($this->process->hasBeenSignaled()) {
                $signal = $this->getSignalText($this->process->getTermSignal());
            } elseif ($this->process->hasBeenStopped()) {
                $signal = $this->getSignalText($this->process->getStopSignal());
            }

            $output .= sprintf(
                "\n# Process terminated with exit code %s\n# Result: %s%s\n",
                $this->process->getExitCode(),
                $this->process->getExitCodeText(),
                $signal
            );
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
