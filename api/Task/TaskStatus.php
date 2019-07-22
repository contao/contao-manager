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

class TaskStatus
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
     * @var string
     */
    private $summary = '';

    /**
     * @var string
     */
    private $detail = '';

    /**
     * @var string|false|null
     */
    private $console;

    /**
     * @var bool
     */
    private $cancellable = false;

    /** @var bool */
    private $autoClose = false;

    /**
     * @var bool
     */
    private $audit;

    /**
     * @var string
     */
    private $status = self::STATUS_ACTIVE;

    /**
     * Constructor.
     *
     * @param string $title
     * @param bool   $audit
     */
    public function __construct(string $title, bool $audit = false)
    {
        $this->title = $title;
        $this->audit = $audit;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return TaskStatus
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     *
     * @return TaskStatus
     */
    public function setDetail(string $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @param string|false|null $console
     */
    public function setConsole($console): self
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Adds output to the console log.
     *
     * @param string      $console
     * @param string|null $title
     */
    public function addConsole(string $console, string $title = null): void
    {
        if (null !== $title) {
            $console = sprintf("%s\n\n%s", $title, $console);
        }

        if (!$console) {
            return;
        }

        if ($this->console) {
            $console = $this->console."\n\n".$console;
        }

        $this->console = $console;
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
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return TaskStatus
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return self::STATUS_ACTIVE === $this->status;
    }

    public function isComplete(): bool
    {
        return self::STATUS_COMPLETE === $this->status;
    }

    public function isStopped(): bool
    {
        return self::STATUS_STOPPED === $this->status;
    }

    public function hasError(): bool
    {
        return self::STATUS_ERROR === $this->status;
    }
}
