<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Process;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\Exception\InvalidJsonException;
use Terminal42\BackgroundProcess\Forker\ForkerInterface;
use Terminal42\BackgroundProcess\ProcessController;

/**
 * Creates foreground and background processes for the Contao or Manager console.
 */
class ConsoleProcessFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * Constructor.
     *
     * @param ApiKernel  $kernel
     * @param ServerInfo $serverInfo
     */
    public function __construct(ApiKernel $kernel, ServerInfo $serverInfo)
    {
        $this->kernel = $kernel;
        $this->serverInfo = $serverInfo;
    }

    /**
     * Gets the path to manager console or Phar file.
     *
     * @return string
     */
    public function getManagerConsolePath()
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
    public function getContaoConsolePath()
    {
        return $this->kernel->getProjectDir().'/vendor/contao/manager-bundle/bin/contao-console';
    }

    /**
     * Gets the path to the Contao API.
     *
     * @return string
     */
    public function getContaoApiPath()
    {
        return $this->kernel->getProjectDir().'/vendor/contao/manager-bundle/bin/contao-api';
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
     * Creates a foreground process for the Contao API.
     *
     * @param array $arguments
     *
     * @return Process
     */
    public function createContaoApiProcess(array $arguments)
    {
        return $this->createForegroundProcess($this->getContaoApiPath(), $arguments);
    }

    /**
     * Restores the ProcessController for given task ID.
     *
     * @param string $id
     *
     * @throws ApiProblemException
     *
     * @return ProcessController
     */
    public function restoreBackgroundProcess($id)
    {
        try {
            $process = ProcessController::restore($this->kernel->getConfigDir(), $id);
        } catch (InvalidJsonException $e) {
            $problem = (new ApiProblem($e->getMessage()))
                ->setDetail($e->getJsonErrorMessage()."\n\n".$e->getContent())
            ;

            throw new ApiProblemException($problem, $e);
        }

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
            $this->kernel->getProjectDir(),
            $this->serverInfo->getPhpEnv()
        ))->inheritEnvironmentVariables()->setTimeout(0);
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
            $this->kernel->getConfigDir(),
            $this->buildCommandLine($console, $arguments),
            $this->kernel->getProjectDir(),
            $id
        );

        $process->setTimeout(0);

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

        foreach ($this->serverInfo->getProcessForkers() as $class) {
            /** @var ForkerInterface $forker */
            $forker = new $class(
                $backgroundCommand,
                $this->serverInfo->getPhpEnv(),
                $this->logger
            );

            $forker->setTimeout(5000);

            $process->addForker($forker);
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
        $defaultArgs = ['-q'];

        if (file_exists($this->kernel->getConfigDir().'/php.ini')) {
            $defaultArgs[] = '-c';
            $defaultArgs[] = $this->kernel->getConfigDir().'/php.ini';
        }

        if (null !== ($phpCli = $this->serverInfo->getPhpExecutable())) {
            $cmd = $phpCli;
            $arguments = array_merge($defaultArgs, $this->serverInfo->getPhpArguments(), [$console], $arguments);
        } else {
            $cmd = $console;
        }

        return escapeshellcmd($cmd).' '.implode(' ', array_map('escapeshellarg', $arguments)).' 2>&1';
    }
}
