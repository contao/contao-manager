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
use Terminal42\BackgroundProcess\Forker\ForkerInterface;
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
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * @var null|LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ApiKernel            $kernel
     * @param ManagerConfig        $config
     * @param ServerInfo           $serverInfo
     * @param LoggerInterface|null $logger
     */
    public function __construct(ApiKernel $kernel, ManagerConfig $config, ServerInfo $serverInfo, LoggerInterface $logger = null)
    {
        $this->kernel = $kernel;
        $this->config = $config;
        $this->serverInfo = $serverInfo;
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
     * @param string|null $id
     *
     * @return ProcessController
     */
    public function createManagerConsoleBackgroundProcess(array $arguments, $id = null)
    {
        return $this->createBackgroundProcess($this->getManagerConsolePath(), $arguments, $id);
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
     * Creates a background process for the Contao console.
     *
     * @param array       $arguments
     * @param string|null $id
     *
     * @return ProcessController
     */
    public function createContaoConsoleBackgroundProcess(array $arguments, $id = null)
    {
        return $this->createBackgroundProcess($this->getContaoConsolePath(), $arguments, $id);
    }

    /**
     * Restores the ProcessController for given task ID.
     *
     * @param string $id
     *
     * @return ProcessController
     */
    public function restoreBackgroundProcess($id)
    {
        $process = ProcessController::restore($this->kernel->getManagerDir(), $id);

        $this->addForkers($process);

        return $process;
    }

    /**
     * Creates a foreground process.
     *
     * @param string $console
     * @param array  $arguments
     *
     * @return Process
     */
    private function createForegroundProcess($console, array $arguments)
    {
        return (new Process(
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getContaoDir(),
            array_map(function () { return false; }, $_ENV)
        ))->inheritEnvironmentVariables();
    }

    /**
     * Creates a background process controller.
     *
     * @param string      $console
     * @param array       $arguments
     * @param string|null $id
     *
     * @return ProcessController
     */
    private function createBackgroundProcess($console, array $arguments, $id = null)
    {
        $process = ProcessController::create(
            $this->kernel->getManagerDir(),
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getContaoDir(),
            $id
        );

        $this->addForkers($process);

        return $process;
    }

    /**
     * Adds forker instances to the process controller.
     *
     * @param ProcessController $process
     */
    private function addForkers(ProcessController $process)
    {
        $backgroundCommand = $this->buildCommandLine(
            $this->getManagerConsolePath(),
            [
                '--no-interaction',
                'run',
            ]
        );

        $serverInfo = $this->serverInfo->getData();
        $forkers = [DisownForker::class, NohupForker::class];
        $env = array_map(function () { return false; }, $_ENV);

        if (isset($serverInfo['provider']['process_forker'])) {
            $forkers = (array) $serverInfo['provider']['process_forker'];
        }

        foreach ($forkers as $class) {
            /** @var ForkerInterface $forker */
            $forker = new $class($backgroundCommand, $env, $this->logger);
            $process->addForker($forker->setTimeout(5000));
        }
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
            $arguments = array_merge(['-q'], $this->serverInfo->getPhpArguments($phpCli), [$console], $arguments);
        } else {
            $cmd = $console;
        }

        return escapeshellcmd($cmd).' '.implode(' ', array_map('escapeshellarg', $arguments));
    }

    /**
     * Gets the path to manager console or Phar file.
     *
     * @return string
     */
    private function getManagerConsolePath()
    {
        if ('' !== ($phar = \Phar::running(false))) {
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
