<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Process\Forker\InlineForker;
use Contao\ManagerApi\Process\ProcessController;
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

    public function getDetails(): ?string
    {
        return '';
    }

    public function getConsole(): ConsoleOutput
    {
        $console = new ConsoleOutput();

        if (!$this->process->isStarted()) {
            return $console;
        }

        $console->add(
            $this->process->getOutput().$this->getProcessError(),
            '$ '.$this->process->getCommandLine()
        );

        return $console;
    }

    public function isStarted(): bool
    {
        return $this->process->isStarted();
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function hasError(): bool
    {
        return $this->process->isTerminated() && $this->process->getExitCode() > 0;
    }

    public function run(): void
    {
        if (!$this->process->isStarted()) {
            $this->process->start();
        }
    }

    public function abort(): void
    {
        if ($this->isRunning()) {
            $this->process->stop();
        }
    }

    public function delete(): void
    {
        $this->process->delete();
    }

    protected function getProcessError(): string
    {
        $output = '';

        if ($this->process->isTerminated()) {
            $signal = '';

            if ($this->process->hasBeenSignaled()) {
                $signal = $this->getSignalText($this->process->getTermSignal());
            } elseif ($this->process->hasBeenStopped()) {
                $signal = $this->getSignalText($this->process->getStopSignal());
            }

            if ($this->process instanceof ProcessController && $this->process->getForker() instanceof InlineForker) {
                $output = <<<'OUTPUT'

# WARNING: INLINE PROCESS EXECUTION
# Background processes are not support by your server/shell.
# The operation might have be affected by script runtime (e.g. stop after 30 seconds).
#
OUTPUT;
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
     */
    private function getSignalText($signal): string
    {
        if (isset(static::$signals[$signal])) {
            return sprintf(' [%s]', static::$signals[$signal]);
        }

        return sprintf(' [signal %s]', $signal);
    }
}
