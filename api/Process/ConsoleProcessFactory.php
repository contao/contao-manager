<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Process;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\Forker\DebugForker;
use Terminal42\BackgroundProcess\Forker\DisownForker;
use Terminal42\BackgroundProcess\Forker\NohupForker;
use Terminal42\BackgroundProcess\ProcessController;

/**
 * Creates foreground and background processes for the Contao or Manager console.
 */
class ConsoleProcessFactory
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
     * @var null|LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ApiKernel            $kernel
     * @param ManagerConfig        $config
     * @param LoggerInterface|null $logger
     */
    public function __construct(ApiKernel $kernel, ManagerConfig $config, LoggerInterface $logger = null)
    {
        $this->kernel = $kernel;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Creates a foreground process for the Manager console.
     *
     * @param array $arguments
     *
     * @return Process
     */
    public function createManagerConsoleProcess(array $arguments)
    {
        return $this->createForegroundProcess($this->getManagerConsolePath(), $arguments);
    }

    /**
     * Creates a background process for the Manager console.
     *
     * @param array       $arguments
     * @param string|null $uuid
     *
     * @return ProcessController
     */
    public function createManagerConsoleBackgroundProcess(array $arguments, $uuid = null)
    {
        return $this->createBackgroundProcess($this->getManagerConsolePath(), $arguments, $uuid);
    }

    /**
     * Creates a foreground process for the Contao console.
     *
     * @param array $arguments
     *
     * @return Process
     */
    public function createContaoConsoleProcess(array $arguments)
    {
        return $this->createForegroundProcess($this->getContaoConsolePath(), $arguments);
    }

    /**
     * Creates a foreground process for the Contao console.
     *
     * @param array       $arguments
     * @param string|null $uuid
     *
     * @return ProcessController
     */
    public function createContaoConsoleBackgroundProcess(array $arguments, $uuid = null)
    {
        return $this->createBackgroundProcess($this->getContaoConsolePath(), $arguments, $uuid);
    }

    /**
     * Restores the ProcessController for given task ID.
     *
     * @param string $uuid
     *
     * @return ProcessController
     */
    public function restoreBackgroundProcess($uuid)
    {
        return ProcessController::restore($this->kernel->getManagerDir(), $uuid);
    }

    /**
     * @param string $console
     * @param array  $arguments
     *
     * @return Process
     */
    private function createForegroundProcess($console, array $arguments)
    {
        return new Process(
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getContaoDir()
        );
    }

    /**
     * @param string      $console
     * @param array       $arguments
     * @param string|null $uuid
     *
     * @return ProcessController
     */
    private function createBackgroundProcess($console, array $arguments, $uuid = null)
    {
        $process = ProcessController::create(
            $this->kernel->getManagerDir(),
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getContaoDir(),
            $uuid
        );

        $backgroundCommand = $this->buildCommandLine(
            $this->getManagerConsolePath(),
            [
                '--no-interaction',
                'background-task:run',
            ]
        );

        if ($this->kernel->isDebug() && $this->config->get('fork_debug')) {
            $process->addForker(new DebugForker($backgroundCommand, $this->logger));
        }

        $process->addForker(new DisownForker($backgroundCommand, $this->logger));
        $process->addForker(new NohupForker($backgroundCommand, $this->logger));

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
        if (null !== ($phpCli = $this->config->getPhpExecutable())) {
            $cmd = $phpCli;
            array_unshift($arguments, $console);
        } else {
            $cmd = $console;
        }

        $args = implode(' ', array_map('escapeshellarg', $arguments));

        return escapeshellcmd($cmd).' '.$args;
    }

    /**
     * Gets the path to manager console or Phar file.
     *
     * @return string
     */
    private function getManagerConsolePath()
    {
        if ('' !== ($phar = \Phar::running())) {
            return substr($phar, 7);
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
