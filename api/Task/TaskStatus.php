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
use Contao\ManagerApi\TaskOperation\SponsoredOperationInterface;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;

final class TaskStatus implements \JsonSerializable
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_ERROR = 'error';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ABORTING = 'aborting';

    public const STATUS_STOPPED = 'stopped';

    private bool $cancellable = false;

    private bool $autoClose = false;

    private bool $audit = false;

    private bool $abort = false;

    public function __construct(
        private readonly string $id,
        private readonly string $title,
        /**
         * @var array<TaskOperationInterface>
         */
        private readonly array $operations,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getConsole(): string
    {
        $console = new ConsoleOutput();

        foreach ($this->operations as $operation) {
            $console->add((string) $operation->getConsole());
        }

        return (string) $console;
    }

    /**
     * @return array<TaskOperationInterface>
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
        foreach ($this->operations as $i => $operation) {
            if ($this->abort) {
                if ($operation->isRunning()) {
                    return self::STATUS_ABORTING;
                }

                continue;
            }

            if ($operation->hasError()) {
                if (
                    $operation->continueOnError()
                    && (
                        !isset($this->operations[$i + 1])
                        || $this->operations[$i + 1]->isRunning()
                        || $this->operations[$i + 1]->isSuccessful()
                    )
                ) {
                    continue;
                }

                return self::STATUS_PAUSED;
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

    public function jsonSerialize(): array
    {
        $operations = [];
        $sponsor = null;

        $isNext = true;
        $hasError = false;
        $canContinue = false;

        foreach ($this->operations as $operation) {
            $status = $this->getOperationStatus($operation, $isNext);

            $operations[] = [
                'summary' => $operation->getSummary(),
                'details' => $operation->getDetails(),
                'console' => (string) $operation->getConsole(),
                'status' => $hasError && !$canContinue ? self::STATUS_STOPPED : $status,
            ];

            if ($operation instanceof SponsoredOperationInterface) {
                $sponsor = $operation->getSponsor();
            }

            $isNext = $operation->isSuccessful();
            $canContinue = $canContinue || (!$hasError && $operation->hasError() && $operation->continueOnError());
            $hasError = $hasError || $operation->hasError();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'console' => $this->getConsole(),
            'cancellable' => $this->cancellable,
            'continuable' => $canContinue,
            'autoclose' => $this->autoClose,
            'audit' => $this->audit,
            'status' => $this->getStatus(),
            'operations' => $operations,
            'sponsor' => $sponsor,
        ];
    }

    private function getOperationStatus(TaskOperationInterface $operation, bool $isNext = false): string
    {
        return match (true) {
            $operation->isRunning() => self::STATUS_ACTIVE,
            $operation->isSuccessful() => self::STATUS_COMPLETE,
            $operation->hasError() => self::STATUS_ERROR,
            $isNext || $operation->isStarted() => self::STATUS_ACTIVE,
            default => 'pending',
        };
    }
}
