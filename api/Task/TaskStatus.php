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

use Contao\ManagerApi\TaskOperation\ConsoleOutput;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;

final class TaskStatus implements \JsonSerializable
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_ERROR = 'error';
    public const STATUS_ABORTING = 'aborting';
    public const STATUS_STOPPED = 'stopped';

    /**
     * @var string
     */
    private $title;

    /**
     * @var TaskOperationInterface[]
     */
    private $operations;

    /**
     * @var bool
     */
    private $cancellable = false;

    /** @var bool */
    private $autoClose = false;

    /**
     * @var bool
     */
    private $audit = false;

    /**
     * @var bool
     */
    private $abort = false;

    public function __construct(string $title, array $operations)
    {
        $this->title = $title;
        $this->operations = $operations;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getConsole()
    {
        $console = new ConsoleOutput();

        foreach ($this->operations as $operation) {
            $console->add((string) $operation->getConsole());
        }

        return (string) $console;
    }

    /**
     * @return TaskOperationInterface[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function isCancellable(): bool
    {
        return $this->cancellable;
    }

    public function setCancellable(bool $stoppable): self
    {
        $this->cancellable = $stoppable;

        return $this;
    }

    public function canAutoClose(): bool
    {
        return $this->autoClose;
    }

    public function setAutoClose(bool $autoClose): self
    {
        $this->autoClose = $autoClose;

        return $this;
    }

    public function hasAudit(): bool
    {
        return $this->audit;
    }

    public function setAudit(bool $audit): self
    {
        $this->audit = $audit;

        return $this;
    }

    public function getStatus(): string
    {
        foreach ($this->operations as $operation) {
            if ($this->abort) {
                if ($operation->isRunning()) {
                    return self::STATUS_ABORTING;
                }

                continue;
            }

            if ($operation->hasError()) {
                return self::STATUS_ERROR;
            }

            if (!$operation->isStarted() || $operation->isRunning()) {
                return self::STATUS_ACTIVE;
            }
        }

        if ($this->abort) {
            return self::STATUS_STOPPED;
        }

        return self::STATUS_COMPLETE;
    }

    public function setAborted(): self
    {
        $this->abort = true;

        return $this;
    }

    public function isActive(): bool
    {
        return self::STATUS_ACTIVE === $this->getStatus();
    }

    public function isComplete(): bool
    {
        return self::STATUS_COMPLETE === $this->getStatus();
    }

    public function isStopped(): bool
    {
        return self::STATUS_STOPPED === $this->getStatus();
    }

    public function hasError(): bool
    {
        return self::STATUS_ERROR === $this->getStatus();
    }

    public function jsonSerialize()
    {
        $operations = [];

        $isNext = true;
        $hasError = false;

        foreach ($this->operations as $operation) {
            $operations[] = [
                'summary' => $operation->getSummary(),
                'details' => $operation->getDetails(),
                'console' => (string) $operation->getConsole(),
                'status' => $hasError ? self::STATUS_STOPPED : $this->getOperationStatus($operation, $isNext),
            ];

            $isNext = $operation->isSuccessful();
            $hasError = $hasError || $operation->hasError();
        }

        return [
            'title' => $this->getTitle(),
            'console' => $this->getConsole(),
            'cancellable' => $this->isCancellable(),
            'autoclose' => $this->canAutoClose(),
            'audit' => $this->hasAudit(),
            'status' => $this->getStatus(),
            'operations' => $operations,
        ];
    }

    private function getOperationStatus(TaskOperationInterface $operation, bool $isNext = false): string
    {
        switch (true) {
            case $operation->isRunning():
                return self::STATUS_ACTIVE;

            case $operation->isSuccessful():
                return self::STATUS_COMPLETE;

            case $operation->hasError():
                return self::STATUS_ERROR;
        }

        if ($operation->isStarted() || $isNext) {
            return self::STATUS_ACTIVE;
        }

        return 'pending';
    }
}
