<?php

namespace Contao\ManagerApi\Process;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;
use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\ProcessController;

class CommandLine
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ManagerConfig
     */
    private $config;

    /**
     * Constructor.
     *
     * @param ApiKernel     $kernel
     * @param ManagerConfig $config
     */
    public function __construct(ApiKernel $kernel, ManagerConfig $config)
    {
        $this->kernel = $kernel;
        $this->config = $config;
    }

    /**
     * @param array $arguments
     *
     * @return Process
     */
    public function runManagerConsole(array $arguments)
    {
        return $this->runForegroundProcess($this->getManagerConsolePath(), $arguments);
    }

    /**
     * @param array $arguments
     *
     * @return ProcessController
     */
    public function runManagerConsoleInBackground(array $arguments)
    {
        return $this->runBackgroundProcess($this->getManagerConsolePath(), $arguments);
    }

    /**
     * @param array $arguments
     *
     * @return Process
     */
    public function runContaoConsole(array $arguments)
    {
        return $this->runForegroundProcess($this->getContaoConsolePath(), $arguments);
    }

    /**
     * @param array $arguments
     *
     * @return ProcessController
     */
    public function runContaoConsoleInBackground(array $arguments)
    {
        return $this->runBackgroundProcess($this->getContaoConsolePath(), $arguments);
    }

    /**
     * @param string $console
     * @param array  $arguments
     *
     * @return Process
     */
    private function runForegroundProcess($console, array $arguments)
    {
        $process = new Process(
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getContaoDir()
        );

        $process->run();

        return $process;
    }

    /**
     * @param string $console
     * @param array  $arguments
     *
     * @return ProcessController
     */
    private function runBackgroundProcess($console, array $arguments)
    {
        $process = ProcessController::create(
            $this->kernel->getManagerDir(),
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getContaoDir()
        );

        $process->start();

        return $process;
    }

    /**
     * Builds a command line with PHP runtime from console path and arguments.
     *
     * @param string $console
     * @param array  $arguments
     *
     * @return string
     */
    private function buildCommandLine($console, array $arguments)
    {
        return sprintf(
            '%s %s %s %s',
            escapeshellcmd($this->config->getPhpExecutable()),
            implode(' ', array_map('escapeshellarg', $this->config->getPhpArguments())),
            escapeshellarg($console),
            implode(' ', array_map('escapeshellarg', $arguments))
        );
    }

    /**
     * Gets the path to manager console or Phar file.
     *
     * @return string
     */
    private function getManagerConsolePath()
    {
        if ('' !== ($phar = \Phar::running())) {
            return $phar;
        }

        return $this->kernel->getRootDir().'/console';
    }

    /**
     * Gets the path to the Contao console.
     *
     * @return string
     */
    private function getContaoConsolePath()
    {
        return $this->kernel->getContaoDir().'/vendor/bin/contao-console';
    }
}
