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

/**
 * @ServiceTag("monolog.logger", channel="tasks")
 */
class TaskManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $configFile;

    private string $logFile;

    /**
     * @var array<TaskInterface>
     */
    private array $tasks = [];

    /**
     * Constructor.
     *
     * @param iterable<TaskInterface> $tasks
     */
    public function __construct(iterable $tasks, ApiKernel $kernel, private ConsoleProcessFactory $processFactory, private ?\Symfony\Component\Filesystem\Filesystem $filesystem = null)
    {
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
            $this->logger->info('Created new task', ['name' => $name, 'options' => $options, 'class' => $task::class]);
        }

        $this->processFactory->createManagerConsoleBackgroundProcess(['task:update', '--poll']);

        return $task->create($config);
    }

    public function updateTask(): ?TaskStatus
    {
        $config = $this->getTaskConfig();

        if ($config === null) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Updating task status', ['name' => $task->getName(), 'class' => $task::class]);
        }

        $status = $task->update($config);

        if (null !== $this->logger && $status->isComplete()) {
            $this->logger->info('Task has been completed', ['name' => $task->getName(), 'class' => $task::class]);
        }

        return $status;
    }

    public function abortTask(): ?TaskStatus
    {
        $config = $this->getTaskConfig();

        if ($config === null) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Aborting task', ['name' => $task->getName(), 'class' => $task::class]);
        }

        return $task->abort($config);
    }

    public function deleteTask(): ?TaskStatus
    {
        $config = $this->getTaskConfig();

        if ($config === null) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Deleting task', ['name' => $task->getName(), 'class' => $task::class]);
        }

        $status = $task->create($config);

        if ($status->isActive() || !$task->delete($config)) {
            throw new \RuntimeException('Active task cannot be deleted');
        }

        $this->saveConsoleOutput($status->getConsole());

        return $status;
    }

    private function loadTask(TaskConfig $config): TaskInterface
    {
        $name = $config->getName();

        if (!isset($this->tasks[$name])) {
            throw new \InvalidArgumentException(sprintf('Unable to get task "%s".', $name));
        }

        $task = $this->tasks[$name];

        if (!$task instanceof TaskInterface) {
            throw new \RuntimeException(sprintf('"%s" is not an instance of "%s"', $task::class, TaskInterface::class));
        }

        return $task;
    }

    private function getTaskConfig(): ?TaskConfig
    {
        if ($this->filesystem->exists($this->configFile)) {
            try {
                return new TaskConfig($this->configFile, null, null, $this->filesystem);
            } catch (\Exception) {
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
