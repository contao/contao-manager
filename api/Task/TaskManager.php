<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

class TaskManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Filesystem|null
     */
    private $filesystem;

    /**
     * @var ContainerInterface
     */
    private $taskLocator;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var string
     */
    private $configFile;

    /**
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ContainerInterface    $taskLocator
     * @param ConsoleProcessFactory $processFactory
     * @param Filesystem|null       $filesystem
     */
    public function __construct(ApiKernel $kernel, ContainerInterface $taskLocator, ConsoleProcessFactory $processFactory, Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem;
        $this->taskLocator = $taskLocator;
        $this->processFactory = $processFactory;

        $this->configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'task.json';
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function supportsTask($name)
    {
        return $this->taskLocator->has($name);
    }

    /**
     * @return bool
     */
    public function hasTask()
    {
        return $this->filesystem->exists($this->configFile);
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return TaskStatus
     */
    public function createTask($name, array $options)
    {
        if ($this->hasTask()) {
            throw new \RuntimeException('A task already exists.');
        }

        $config = new TaskConfig($this->configFile, $name, $options, $this->filesystem);
        $config->save();

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Created new task', ['name' => $name, 'options' => $options, 'class' => get_class($task)]);
        }

        $this->processFactory->createManagerConsoleBackgroundProcess(['task:update', '--poll']);

        return $task->create($config);
    }

    /**
     * @return TaskStatus|null
     */
    public function updateTask()
    {
        $config = $this->getTaskConfig();

        if (!$config) {
            return null;
        }

        $task = $this->loadTask($config);

        if (null !== $this->logger) {
            $this->logger->info('Updating task status', ['name' => $task->getName(), 'class' => get_class($task)]);
        }

        $status = $task->update($config);

        if ($status->isComplete() && null !== $this->logger) {
            $this->logger->info('Task has been completed', ['name' => $task->getName(), 'class' => get_class($task)]);
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
            $this->logger->info('Aborting task', ['name' => $task->getName(), 'class' => get_class($task)]);
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
            $this->logger->info('Deleting task', ['name' => $task->getName(), 'class' => get_class($task)]);
        }

        $status = $task->update($config);

        if ($status->isActive() || !$task->delete($config)) {
            throw new \RuntimeException('Active task cannot be deleted');
        }

        return $status;
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskInterface
     */
    private function loadTask(TaskConfig $config)
    {
        $name = $config->getName();

        try {
            $task = $this->taskLocator->get($name);
        } catch (ContainerExceptionInterface $e) {
            throw new \InvalidArgumentException(sprintf('Unable to get task "%s".', $name));
        }

        if (!$task instanceof TaskInterface) {
            throw new \RuntimeException(
                sprintf('"%s" is not an instance of "%s"', get_class($task), TaskInterface::class)
            );
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
}
