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

    public function __construct(private array $result)
    {
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

    public function getSponsor(): array
    {
        return $this->result['sponsoredBy'];
    }

    public function getWaitingTime(): int
    {
        if (self::STATUS_QUEUED !== $this->result['status']) {
            return 0;
        }

        $currentPos = $this->result['queuePosition'] ?: $this->result['stats']['numberOfJobsInQueue'];

        return (int) round(
            $currentPos
            * $this->result['stats']['averageProcessingTimeInMs'] / 1000
            / max($this->result['stats']['numberOfWorkers'], 1),
        );
    }

    public function getJobsInQueue(): int
    {
        return (int) $this->result['queuePosition'] ?: $this->result['stats']['numberOfJobsInQueue'];
    }

    public function getWorkers(): int
    {
        return (int) $this->result['stats']['numberOfWorkers'];
    }

    public function getVersion(): string
    {
        return isset($this->result['stats']['appVersion']) ? 'v'.$this->result['stats']['appVersion'] : '';
    }

    public function isQueued(): bool
    {
        return self::STATUS_QUEUED === $this->getStatus();
    }

    public function isProcessing(): bool
    {
        return self::STATUS_PROCESSING === $this->getStatus();
    }

    public function isSuccessful(): bool
    {
        return self::STATUS_FINISHED === $this->getStatus();
    }

    public function isFailed(): bool
    {
        return self::STATUS_ERROR === $this->getStatus();
    }

    public function jsonSerialize()
    {
        return $this->result;
    }
}
