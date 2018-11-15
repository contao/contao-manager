<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractInlineOperation implements TaskOperationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TaskConfig
     */
    protected $taskConfig;

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
        return TaskStatus::STATUS_ACTIVE === $this->taskConfig->getState($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return TaskStatus::STATUS_COMPLETE === $this->taskConfig->getState($this->getName());
    }

    public function hasError()
    {
        return TaskStatus::STATUS_ERROR === $this->taskConfig->getState($this->getName());
    }

    public function run()
    {
        if ($this->isStarted()) {
            return;
        }

        $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ACTIVE);

        try {
            $success = (bool) $this->doRun();
        } catch (\Error $e) {
            $this->taskConfig->setState($this->getName().'.error', $e->getMessage());
            $success = false;
        } catch (\Exception $e) {
            $this->taskConfig->setState($this->getName().'.error', $e->getMessage());
            $success = false;
        }

        if ($success) {
            $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_COMPLETE);
        } else {
            $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function abort()
    {
        $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
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
        if ($error = $this->taskConfig->getState($this->getName().'.error')) {
            $status->addConsole($error);
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
     * @throws \Exception
     *
     * @return bool
     */
    abstract protected function doRun();
}
