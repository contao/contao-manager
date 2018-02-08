<?php

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\ApiKernel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class TaskManager
{
    /**
     * @var Filesystem|null
     */
    private $filesystem;

    /**
     * @var TaskInterface
     */
    private $task = false;

    /**
     * @var ContainerInterface
     */
    private $taskLocator;

    /**
     * @var string
     */
    private $configFile;

    /**
     * Constructor.
     *
     * @param ApiKernel          $kernel
     * @param ContainerInterface $taskLocator
     * @param Filesystem|null    $filesystem
     */
    public function __construct(ApiKernel $kernel, ContainerInterface $taskLocator, Filesystem $filesystem = null)
    {
        $this->configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'task.json';
        $this->filesystem = $filesystem;
        $this->taskLocator = $taskLocator;
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
    public function startTask($name, array $options)
    {
        if ($this->hasTask()) {
            throw new \RuntimeException('A task already exists.');
        }

        $config = new TaskConfig($this->configFile, $name, $options);
        $task = $this->loadTask($config);

        $config->save();

        return $task->update($config);
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

        return $this->loadTask($config)->update($config);
    }

    /**
     * @return TaskStatus|null
     */
    public function stopTask()
    {
        $config = $this->getTaskConfig();

        if (!$config) {
            return null;
        }

        return $this->loadTask($config)->stop($config);
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

        $status = $this->loadTask($config)->delete($config);

        if (!$status->isActive()) {
            $this->filesystem->remove($this->configFile);
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
                return new TaskConfig($this->configFile);
            } catch (\Exception $e) {
                $this->filesystem->remove($this->configFile);
            }
        }

        return null;
    }
}
