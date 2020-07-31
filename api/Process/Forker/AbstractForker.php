<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Process\Forker;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

abstract class AbstractForker implements ForkerInterface
{
    /**
     * @var array
     */
    protected $command;

    /**
     * @var array|null
     */
    protected $env;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    private $timeout = 500;

    public function __construct(array $command, array $env = null, LoggerInterface $logger = null)
    {
        $this->command = $command;
        $this->env = $env;
        $this->logger = $logger;
    }

    public function setCommand(array $command): ForkerInterface
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): array
    {
        return $this->command;
    }

    public function setTimeout(int $timeout): ForkerInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    protected function startCommand(string $commandline): Process
    {
        if (null !== $this->logger) {
            $this->logger->info(
                'Starting "{commandline}" with {forker_class}',
                [
                    'commandline' => $commandline,
                    'forker_class' => static::class,
                ]
            );
        }

        $process = Process::fromShellCommandline($commandline);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        $process->start(null, $this->env ?: []);

        usleep($this->timeout);

        if (null !== $this->logger && !$process->isStarted()) {
            $this->logger->error(
                'Process did not start correctly',
                [
                    'commandline' => $commandline,
                    'forker_class' => static::class,
                    'exit_code' => $process->getExitCode(),
                    'exit_text' => $process->getExitCodeText(),
                    'stopped' => $process->hasBeenStopped(),
                    'signaled' => $process->hasBeenSignaled(),
                    'stopsignal' => $process->getStopSignal(),
                    'termsignal' => $process->getTermSignal(),
                ]
            );
        }

        return $process;
    }

    /**
     * Escapes a string to be used as a shell argument.
     *
     * @see Process::escapeArgument()
     */
    protected function escapeArgument(?string $argument): string
    {
        if ('' === $argument || null === $argument) {
            return '""';
        }
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            return "'".str_replace("'", "'\\''", $argument)."'";
        }
        if (false !== strpos($argument, "\0")) {
            $argument = str_replace("\0", '?', $argument);
        }
        if (!preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
            return $argument;
        }
        $argument = preg_replace('/(\\\\+)$/', '$1$1', $argument);

        return '"'.str_replace(['"', '^', '%', '!', "\n"], ['""', '"^^"', '"^%"', '"^!"', '!LF!'], $argument).'"';
    }
}
