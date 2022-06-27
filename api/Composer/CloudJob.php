<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Composer;

class CloudJob implements \JsonSerializable
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_ERROR = 'finished_with_errors';

    public const LINK_JSON = 'composerJson';
    public const LINK_LOCK = 'composerLock';
    public const LINK_OUTPUT = 'composerOutput';

    /**
     * @var array
     */
    private $result;

    public function __construct(array $result)
    {
        $this->result = $result;
    }

    public function getId()
    {
        return $this->result['jobId'];
    }

    public function getStatus()
    {
        return $this->result['status'];
    }

    public function getLink($name)
    {
        return $this->result['links'][$name];
    }

    public function getSponsor()
    {
        return $this->result['sponsoredBy']['name'];
    }

    public function getWaitingTime()
    {
        if (self::STATUS_QUEUED !== $this->result['status']) {
            return 0;
        }

        $currentPos = $this->result['queuePosition'] ?: $this->result['stats']['numberOfJobsInQueue'];

        return (int) round(
            $currentPos
            * $this->result['stats']['averageProcessingTimeInMs'] / 1000
            / max($this->result['stats']['numberOfWorkers'], 1)
        );
    }

    /**
     * @return int
     */
    public function getJobsInQueue()
    {
        return (int) $this->result['queuePosition'] ?: $this->result['stats']['numberOfJobsInQueue'];
    }

    /**
     * @return int
     */
    public function getWorkers()
    {
        return (int) $this->result['stats']['numberOfWorkers'];
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return isset($this->result['stats']['appVersion']) ? 'v'.$this->result['stats']['appVersion'] : '';
    }

    public function isQueued()
    {
        return self::STATUS_QUEUED === $this->getStatus();
    }

    public function isProcessing()
    {
        return self::STATUS_PROCESSING === $this->getStatus();
    }

    public function isSuccessful()
    {
        return self::STATUS_FINISHED === $this->getStatus();
    }

    public function isFailed()
    {
        return self::STATUS_ERROR === $this->getStatus();
    }

    public function jsonSerialize()
    {
        return $this->result;
    }
}
