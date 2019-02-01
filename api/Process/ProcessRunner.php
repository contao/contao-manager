<?php

declare(ticks = 1);

namespace Contao\ManagerApi\Process;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessRunner extends AbstractProcess
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @var ProcessTimedOutException|null
     */
    private $timeout;

    private $stdin;
    private $stdout;
    private $stderr;

    /**
     * Constructor.
     *
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        $config = static::readConfig($configFile);

        parent::__construct($config['id'], dirname($configFile));

        $commandline = isset($config['commandline']) ? $config['commandline'] : '';
        $cwd = isset($config['cwd']) ? $config['cwd'] : null;

        $this->process = new Process($commandline, $cwd);

        $this->loadConfig($config);
    }

    public function __destruct()
    {
        $this->stop(0);
    }

    public function run($interval = 1)
    {
        $this->start();

        return $this->wait($interval);
    }

    public function start()
    {
        if ($this->process->isStarted()) {
            return;
        }

        $handler = function ($signo = 15) {
            return $this->signalHandler($signo);
        };

        register_shutdown_function($handler);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGHUP, $handler);
            pcntl_signal(SIGINT, $handler);
            pcntl_signal(SIGQUIT, $handler);
            pcntl_signal(SIGTERM, $handler);
        }

        if (is_file($this->inputFile)) {
            $this->stdin = fopen($this->inputFile, 'rb');
            $this->process->setInput($this->stdin);
        }

        $this->process->start(
            function ($type, $data) {
                if (Process::OUT === $type) {
                    $this->addOutput($data);
                } else {
                    $this->addErrorOutput($data);
                }
            }
        );

        $this->saveConfig();
    }

    public function wait($interval)
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

    public function stop($timeout = 10)
    {
        if (!$this->process->isRunning()) {
            return $this->process->getExitCode();
        }

        $exitCode = $this->process->stop($timeout);

        $this->saveConfig();
        $this->close();

        return $exitCode;
    }

    private function close()
    {
        if (is_resource($this->stdin)) {
            fclose($this->stdin);
        }

        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }

        if (is_resource($this->stderr)) {
            fclose($this->stderr);
        }
    }

    private function signalHandler($signo)
    {
        $this->stop(15 === $signo ? 0 : 10);
    }

    public function addOutput($line)
    {
        if (!is_resource($this->stdout)) {
            $this->stdout = fopen($this->outputFile, 'wb');
        }

        fwrite($this->stdout, $line);
    }

    public function addErrorOutput($line)
    {
        if (!is_resource($this->stderr)) {
            $this->stderr = fopen($this->errorOutputFile, 'wb');
        }

        fwrite($this->stderr, $line);
    }

    private function loadConfig(array $config = null)
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

    private function saveConfig()
    {
        $status = $this->process->getStatus();

        $config = [
            'commandline' => $this->process->getCommandLine(),
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
     *
     * @return int
     */
    private function timeoutCode()
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
