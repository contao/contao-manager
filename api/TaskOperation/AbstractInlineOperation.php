<?php

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

abstract class AbstractInlineOperation implements TaskOperationInterface
{
    /**
     * @var TaskConfig
     */
    protected $taskConfig;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * Constructor.
     *
     * @param TaskConfig $taskConfig
     */
    public function __construct(TaskConfig $taskConfig)
    {
        $this->taskConfig = $taskConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return null !== $this->taskConfig->getState($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return (bool) $this->taskConfig->getState($this->getName(), false);
    }

    public function hasError()
    {
        return $this->exception instanceof \Exception;
    }

    public function run()
    {
        if ($this->isStarted()) {
            return;
        }

        try {
            $success = (bool) $this->doRun();
        } catch (\Exception $e) {
            $this->exception = $e;
            $success = false;
        }

        $this->taskConfig->setState($this->getName(), $success);
    }

    /**
     * {@inheritdoc}
     */
    public function abort()
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        // Do nothing
    }

    /**
     * Adds the exception message to the status console.
     *
     * @param TaskStatus $status
     */
    protected function addConsoleStatus(TaskStatus $status)
    {
        if ($this->exception instanceof \Exception) {
            $status->addConsole($this->exception->getMessage());
        }
    }

    /**
     * Gets the name to store this operation state in the config file.
     *
     * @return string
     */
    abstract protected function getName();

    /**
     * Executes the operation and returns whether it was successful.
     *
     * @return bool
     *
     * @throws \Exception
     */
    abstract protected function doRun();
}
