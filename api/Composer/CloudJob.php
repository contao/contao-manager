<?php

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
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_FINISHED = 'finished';
    const STATUS_ERROR = 'finished_with_errors';

    const LINK_JSON = 'composerJson';
    const LINK_LOCK = 'composerLock';
    const LINK_OUTPUT = 'composerOutput';

    /**
     * @var array
     */
    private $result;

    /**
     * Constructor.
     *
     * @param array $result
     */
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

    /**
     * @return int
     */
    public function getWaitingTime()
    {
        return (int) $this->result['stats']['approxWaitingTime'];
    }

    /**
     * @return int
     */
    public function getJobsInQueue()
    {
        return (int) $this->result['stats']['numberOfJobsInQueue'];
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
        return $this->getStatus() === self::STATUS_QUEUED;
    }

    public function isProcessing()
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }

    public function isSuccessful()
    {
        return $this->getStatus() === self::STATUS_FINISHED;
    }

    public function isFailed()
    {
        return $this->getStatus() === self::STATUS_ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->result;
    }
}
