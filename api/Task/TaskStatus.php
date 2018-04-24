<?php

namespace Contao\ManagerApi\Task;

class TaskStatus
{
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR = 'error';
    const STATUS_ABORTING = 'aborting';
    const STATUS_STOPPED = 'stopped';

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
     * @var string|null
     */
    private $console;

    /**
     * @var bool
     */
    private $cancellable = false;

    /**
     * @var bool
     */
    private $cancelled = false;

    /**
     * @var bool
     */
    private $audit = false;

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
    public function __construct($title, $audit = false)
    {
        $this->title = $title;
        $this->audit = $audit;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return TaskStatus
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     *
     * @return TaskStatus
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     *
     * @return TaskStatus
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @param string|null $console
     *
     * @return TaskStatus
     */
    public function setConsole($console)
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
    public function addConsole($console, $title = null)
    {
        if (null !== $title) {
            $console = sprintf("%s\n\n%s", $title, $console);
        }

        if ($this->console) {
            $console = $this->console . "\n\n" . $console;
        }

        $this->console = $console;
    }

    /**
     * @return bool
     */
    public function isCancellable()
    {
        return $this->cancellable;
    }

    /**
     * @param bool $stoppable
     *
     * @return TaskStatus
     */
    public function setCancellable($stoppable)
    {
        $this->cancellable = $stoppable;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAudit()
    {
        return $this->audit;
    }

    /**
     * @param bool $audit
     *
     * @return TaskStatus
     */
    public function setAudit($audit)
    {
        $this->audit = $audit;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return TaskStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isComplete()
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    public function isStopped()
    {
        return $this->status === self::STATUS_STOPPED;
    }

    public function hasError()
    {
        return $this->status === self::STATUS_ERROR;
    }
}
