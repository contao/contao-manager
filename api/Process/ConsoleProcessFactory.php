<?php

declare(strict_types=1);

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
use Contao\ManagerApi\Exception\InvalidJsonException;
use Contao\ManagerApi\Process\Forker\ForkerInterface;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Process\Process;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * Creates foreground and background processes for the Contao or Manager console.
 *
 * @ServiceTag("monolog.logger", channel="tasks")
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
     */
    public function __construct(ApiKernel $kernel, ServerInfo $serverInfo)
    {
        $this->kernel = $kernel;
        $this->serverInfo = $serverInfo;
    }

    /**
     * Gets the path to manager console or Phar file.
     */
    public function getManagerConsolePath(): string
    {
        if ('' !== ($phar = \Phar::running(false))) {
            return $phar;
        }

        return $this->kernel->getRootDir().'/console';
    }

    /**
     * Gets the path to the Contao console.
     */
    public function getContaoConsolePath(): string
    {
        return $this->kernel->getProjectDir().'/vendor/contao/manager-bundle/bin/contao-console';
    }

    /**
     * Gets the path to the Contao API.
     */
    public function getContaoApiPath(): string
    {
        return $this->kernel->getProjectDir().'/vendor/contao/manager-bundle/bin/contao-api';
    }

    /**
     * Creates a foreground process for the Manager console.
     */
    public function createManagerConsoleProcess(array $arguments): Process
    {
        array_unshift($arguments, $this->getManagerConsolePath());

        return $this->createForegroundProcess($arguments);
    }

    /**
     * Creates a background process for the Manager console.
     */
    public function createManagerConsoleBackgroundProcess(array $arguments, string $id = null): ProcessController
    {
        array_unshift($arguments, $this->getManagerConsolePath());

        return $this->createBackgroundProcess($arguments, $id);
    }

    /**
     * Creates a foreground process for the Contao console.
     */
    public function createContaoConsoleProcess(array $arguments): Process
    {
        array_unshift($arguments, $this->getContaoConsolePath());

        return $this->createForegroundProcess($arguments);
    }

    /**
     * Creates a background process for the Contao console.
     */
    public function createContaoConsoleBackgroundProcess(array $arguments, string $id = null): ProcessController
    {
        array_unshift($arguments, $this->getContaoConsolePath());

        return $this->createBackgroundProcess($arguments, $id);
    }

    /**
     * Creates a foreground process for the Contao API.
     */
    public function createContaoApiProcess(array $arguments): Process
    {
        array_unshift($arguments, $this->getContaoApiPath());

        return $this->createForegroundProcess($arguments);
    }

    /**
     * Restores the ProcessController for given task ID.
     *
     * @throws ApiProblemException
     */
    public function restoreBackgroundProcess(string $id): ProcessController
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
     */
    private function createForegroundProcess(array $arguments): Process
    {
        return (new Utf8Process(
            $this->addPhpRuntime($arguments),
            $this->kernel->getProjectDir(),
            $this->serverInfo->getPhpEnv()
        ))->inheritEnvironmentVariables()->setTimeout(0);
    }

    /**
     * Creates a background process controller.
     */
    private function createBackgroundProcess(array $arguments, string $id = null): ProcessController
    {
        $process = ProcessController::create(
            $this->kernel->getConfigDir(),
            $this->addPhpRuntime($arguments),
            $this->kernel->getProjectDir(),
            $id
        );

        $process->setTimeout(0);

        $this->addForkers($process);

        return $process;
    }

    /**
     * Adds forker instances to the process controller.
     */
    private function addForkers(ProcessController $process): void
    {
        $backgroundCommand = $this->addPhpRuntime(
            [
                $this->getManagerConsolePath(),
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
     * Adds PHP runtime to console arguments.
     */
    private function addPhpRuntime(array $arguments): array
    {
        if (null === ($phpCli = $this->serverInfo->getPhpExecutable())) {
            return $arguments;
        }

        $defaultArgs = [$phpCli, '-q'];

        if (file_exists($this->kernel->getConfigDir().'/php.ini')) {
            $defaultArgs[] = '-c';
            $defaultArgs[] = $this->kernel->getConfigDir().'/php.ini';
        }

        $defaultArgs[] = '-d';
        $defaultArgs[] = 'max_execution_time=0';

        $defaultArgs[] = '-d';
        $defaultArgs[] = 'memory_limit=-1';

        $defaultArgs[] = '-d';
        $defaultArgs[] = 'allow_url_fopen=1';

        $defaultArgs[] = '-d';
        $defaultArgs[] = 'date.timezone='.@date_default_timezone_get();

        return array_merge($defaultArgs, $arguments);
    }
}
