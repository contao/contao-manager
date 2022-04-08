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

    public function getDetails(): ?string
    {
        return '';
    }

    public function getConsole(): ConsoleOutput
    {
        return $this->addConsoleOutput(new ConsoleOutput());
    }

    public function isStarted(): bool
    {
        return null !== $this->taskConfig->getState($this->getName());
    }

    public function isRunning(): bool
    {
        return TaskStatus::STATUS_ACTIVE === $this->taskConfig->getState($this->getName());
    }

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
        // Inline task should never need more than 60secs to complete. Assume something went wrong.
        if ($this->isRunning() && $this->taskConfig->getState($this->getName().'.started', 0) < (time() + 60)) {
            $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
            $this->taskConfig->setState($this->getName().'.error', 'Inline operation did not complete within 60 seconds.');
            return;
        }

        // Do not start the inline task again if it has been started
        if ($this->isStarted()) {
            return;
        }

        $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ACTIVE);
        $this->taskConfig->setState($this->getName().'.started', time());
        $success = null;

        register_shutdown_function(function () use (&$success) {
            if (null === $success) {
                $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
            }
        });

        try {
            $success = $this->doRun();
        } catch (\Throwable $e) {
            $this->taskConfig->setState($this->getName().'.error', $e->getMessage());
            $success = false;
        }

        if ($success) {
            $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_COMPLETE);
        } else {
            $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
        }
    }

    public function abort(): void
    {
        if ($this->isRunning()) {
            $this->taskConfig->setState($this->getName(), TaskStatus::STATUS_ERROR);
        }
    }

    public function delete(): void
    {
        // Do nothing
    }

    /**
     * Adds the exception message to the console output.
     */
    protected function addConsoleOutput(ConsoleOutput $console): ConsoleOutput
    {
        if ($error = $this->taskConfig->getState($this->getName().'.error')) {
            $console->add((string) $error);
        }

        return $console;
    }

    /**
     * Gets the name to store this operation state in the config file.
     */
    abstract protected function getName(): string;

    /**
     * Executes the operation and returns whether it was successful.
     *
     * @throws \Exception
     */
    abstract protected function doRun(): bool;
}
