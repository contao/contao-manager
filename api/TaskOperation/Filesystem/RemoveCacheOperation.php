<?php

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
use Symfony\Component\Filesystem\Filesystem;

class RemoveCacheOperation implements TaskOperationInterface
{

    /**
     * @var string
     */
    private $environment;

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var TaskConfig
     */
    private $taskConfig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param string     $environment
     * @param ApiKernel  $kernel
     * @param TaskConfig $taskConfig
     * @param Filesystem $filesystem
     */
    public function __construct($environment, ApiKernel $kernel, TaskConfig $taskConfig, Filesystem $filesystem)
    {
        $this->taskConfig = $taskConfig;
        $this->filesystem = $filesystem;
        $this->environment = $environment;
        $this->kernel = $kernel;
    }

    public function isCancellable()
    {
        return true;
    }

    public function isStarted()
    {
        return (bool) $this->taskConfig->getState(
            'remove-cache@' . $this->getCacheDir()
        );
    }

    public function isRunning()
    {
        return false;
    }

    public function isSuccessful()
    {
        return (bool) $this->taskConfig->getState(
            'remove-cache@' . $this->getCacheDir()
        );
    }

    public function run()
    {
        if (!$this->isStarted()) {
            $this->filesystem->remove($this->getCacheDir());
        }

        $this->taskConfig->setState('remove-cache@' . $this->getCacheDir(), true);
    }

    public function abort()
    {
        return true;
    }

    public function delete()
    {
        return true;
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Deleting cache directory …');
        $status->setDetail($this->getCacheDir());
    }


    private function getCacheDir()
    {
        return $this->kernel->getContaoDir() . '/var/cache/' . $this->environment;
    }
}
