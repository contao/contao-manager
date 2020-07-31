<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use studio24\Rotate\Rotate;
use Symfony\Component\Filesystem\Filesystem;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

/**
 * @ServiceTag("monolog.logger", channel="tasks")
 */
class TaskManager implements LoggerAwareInterface, ServiceAnnotationInterface
{
    use LoggerAwareTrait;

    /**
     * @var Filesystem|null
     */
    private $filesystem;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var string
     */
    private $configFile;

    /**
     * @var string
     */
    private $logFile;

    /**
     * @var TaskInterface[]
     */
    private $tasks = [];

    /**
     * Constructor.
     *
     * @param TaskInterface[] $tasks
     */
    public function __construct(iterable $tasks, ApiKernel $kernel, ConsoleProcessFactory $processFactory, Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem;
        $this->processFactory = $processFactory;
        $this->configFile = $kernel->getConfigDir().\DIRECTORY_SEPARATOR.'task.json';
        $this->logFile = $kernel->getLogDir().'/task-output.log';

        foreach ($tasks as $task) {
            $this->tasks[$task->getName()] = $task;
        }
    }

    public function supportsTask(string $name): bool
    {
        return isset($this->tasks[$name]);
    }

    public function hasTask(): bool
    {
        return $this->filesystem->exists($this->configFile);
    }

    public function createTask(string $name, array $options): TaskStatus
    {
        if ($this->hasTask()) {
            throw new \RuntimeException('A task already exists.');
        }

        $config = new TaskConfig($this->configFile, $name, $options, $this->filesystem);
        $config->save();

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Created new task', ['name' => $name, 'options' => $options, 'class' => \get_class($task)]);
        }

        $this->processFactory->createManagerConsoleBackgroundProcess(['task:update', '--poll']);

        return $task->create($config);
    }

    public function updateTask(): ?TaskStatus
    {
        $config = $this->getTaskConfig();

        if (!$config) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Updating task status', ['name' => $task->getName(), 'class' => \get_class($task)]);
        }

        $status = $task->update($config);

        if ($status->isComplete() && null !== $this->logger) {
            $this->logger->info('Task has been completed', ['name' => $task->getName(), 'class' => \get_class($task)]);
        }

        return $status;
    }

    /**
     * @return TaskStatus|null
     */
    public function abortTask()
    {
        $config = $this->getTaskConfig();

        if (!$config) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Aborting task', ['name' => $task->getName(), 'class' => \get_class($task)]);
        }

        return $task->abort($config);
    }

    /**
     * @return TaskStatus|null
     */
    public function deleteTask()
    {
        $config = $this->getTaskConfig();

        if (!$config) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Deleting task', ['name' => $task->getName(), 'class' => \get_class($task)]);
        }

        $status = $task->update($config);

        if ($status->isActive() || !$task->delete($config)) {
            throw new \RuntimeException('Active task cannot be deleted');
        }

        $this->saveConsoleOutput($status->getConsole());

        return $status;
    }

    /**
     * @return TaskInterface
     */
    private function loadTask(TaskConfig $config)
    {
        $name = $config->getName();

        if (!isset($this->tasks[$name])) {
            throw new \InvalidArgumentException(sprintf('Unable to get task "%s".', $name));
        }

        $task = $this->tasks[$name];

        if (!$task instanceof TaskInterface) {
            throw new \RuntimeException(sprintf('"%s" is not an instance of "%s"', \get_class($task), TaskInterface::class));
        }

        return $task;
    }

    /**
     * @return TaskConfig|null
     */
    private function getTaskConfig()
    {
        if ($this->filesystem->exists($this->configFile)) {
            try {
                return new TaskConfig($this->configFile, null, null, $this->filesystem);
            } catch (\Exception $e) {
                $this->filesystem->remove($this->configFile);
            }
        }

        return null;
    }

    private function saveConsoleOutput(string $output): void
    {
        $rotate = new Rotate($this->logFile);
        $rotate->keep(50);
        $rotate->run();

        $this->filesystem->dumpFile($this->logFile, $output);
    }
}
