<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

declare(ticks=1);

namespace Contao\ManagerApi\Process;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessRunner extends AbstractProcess
{
    private readonly Utf8Process $process;

    private ProcessTimedOutException|null $timeout = null;

    private $stdin;

    private $stdout;

    public function __construct(string $configFile)
    {
        $config = static::readConfig($configFile);

        parent::__construct($config['id'], \dirname($configFile));

        $commandline = $config['commandline'] ?? [];
        $cwd = $config['cwd'] ?? null;

        $this->process = new Utf8Process($commandline, $cwd);

        $this->loadConfig($config);
    }

    public function __destruct()
    {
        $this->stop(0);
    }

    public function run(int $interval = 1): int
    {
        $this->start();

        return $this->wait($interval);
    }

    public function start(): void
    {
        if ($this->process->isStarted()) {
            return;
        }

        $handler = function ($signo = 15) {
            $this->signalHandler($signo);

            return null;
        };

        register_shutdown_function($handler);

        if (\function_exists('pcntl_signal')) {
            pcntl_signal(SIGHUP, $handler);
            pcntl_signal(SIGINT, $handler);
            pcntl_signal(SIGQUIT, $handler);
            pcntl_signal(SIGTERM, $handler);
        }

        if (is_file($this->inputFile)) {
            $this->stdin = fopen($this->inputFile, 'r');
            $this->process->setInput($this->stdin);
        }

        $this->process->start(
            function ($type, $data): void {
                $this->addOutput($data);
            },
        );

        $this->saveConfig();
    }

    public function wait(int $interval): int
    {
        do {
            usleep($interval * 1000000);

            try {
                $this->process->checkTimeout();
                $running = $this->process->isRunning();
            } catch (ProcessTimedOutException $e) {
                $this->timeout = $e;
                $running = false;
            }

            $config = $this->loadConfig();

            if ($running && isset($config['stop']) && $config['stop']) {
                return $this->stop();
            }

            $this->saveConfig();
        } while ($running);

        $this->close();

        return $this->process->getExitCode();
    }

    public function stop(int $timeout = 10): int
    {
        if (!$this->process->isRunning()) {
            return $this->process->getExitCode();
        }

        $exitCode = $this->process->stop($timeout);

        $this->saveConfig();
        $this->close();

        return $exitCode;
    }

    public function addOutput(string $line): void
    {
        if (!\is_resource($this->stdout)) {
            $this->stdout = fopen($this->outputFile, 'w');
        }

        fwrite($this->stdout, $line);
    }

    private function close(): void
    {
        if (\is_resource($this->stdin)) {
            fclose($this->stdin);
        }

        if (\is_resource($this->stdout)) {
            fclose($this->stdout);
        }
    }

    private function signalHandler(int $signo): void
    {
        $this->stop(15 === $signo ? 0 : 10);
    }

    private function loadConfig(array|null $config = null): array
    {
        if (null === $config) {
            $config = static::readConfig($this->setFile);
        }

        $props = [
            'timeout' => 'setTimeout',
            'idleTimeout' => 'setIdleTimeout',
        ];

        foreach ($props as $key => $setter) {
            if (isset($config[$key])) {
                $this->process->{$setter}($config[$key]);
            }
        }

        return $config;
    }

    private function saveConfig(): void
    {
        $status = $this->process->getStatus();

        $config = [
            'cwd' => $this->process->getWorkingDirectory(),
            'timeout' => $this->process->getTimeout(),
            'idleTimeout' => $this->process->getIdleTimeout(),

            'pid' => $this->process->getPid(),
            'status' => $status,
        ];

        if (Process::STATUS_TERMINATED === $status) {
            $config['exitcode'] = $this->process->getExitCode();
            $config['signaled'] = $this->process->hasBeenSignaled();
            $config['termsig'] = $this->process->getTermSignal();
            $config['stopped'] = $this->process->hasBeenStopped();
            $config['stopsig'] = $this->process->getStopSignal();
            $config['timedout'] = $this->timeoutCode();
        }

        static::writeConfig($this->getFile, $config);
    }

    /**
     * Returns the timeout type.
     */
    private function timeoutCode(): int
    {
        if ($this->timeout instanceof ProcessTimedOutException) {
            switch (true) {
                case $this->timeout->isGeneralTimeout():
                    return ProcessTimedOutException::TYPE_GENERAL;

                case $this->timeout->isIdleTimeout():
                    return ProcessTimedOutException::TYPE_IDLE;
            }
        }

        return 0;
    }
}
