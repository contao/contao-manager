<?php

declare(strict_types=1);

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
     */
    public function __construct(TaskConfig $taskConfig)
    {
        $this->taskConfig = $taskConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return null !== $this->taskConfig->getState($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning(): bool
    {
        return TaskStatus::STATUS_ACTIVE === $this->taskConfig->getState($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful(): bool
    {
        return TaskStatus::STATUS_COMPLETE === $this->taskConfig->getState($this->getName());
    }

    public function hasError(): bool
    {
        return TaskStatus::STATUS_ERROR === $this->taskConfig->getState($this->getName());
    }

    public function run(): void
    {
        if ($this->isStarted()) {
            return;
        }

        $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ACTIVE);

        try {
            $success = $this->doRun();
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
    public function abort(): void
    {
        $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): void
    {
        // Do nothing
    }

    /**
     * Adds the exception message to the status console.
     */
    protected function addConsoleStatus(TaskStatus $status): void
    {
        if ($error = $this->taskConfig->getState($this->getName().'.error')) {
            $status->addConsole((string) $error);
        }
    }

    /**
     * Gets the name to store this operation state in the config file.
     *
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * Executes the operation and returns whether it was successful.
     *
     * @throws \Exception
     *
     * @return bool
     */
    abstract protected function doRun(): bool;
}
