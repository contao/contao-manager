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

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * Serializes task operations across web requests and CLI processes.
 *
 * The lock uses a separate file in the manager directory because saving task.json
 * replaces the file. Locking task.json itself would leave the lock attached to
 * the old file and allow another process to lock its replacement.
 *
 * If another process holds the lock, fail immediately. Web requests receive a 503
 * response with Retry-After so the frontend can retry the same request. CLI
 * commands report the error and exit with a nonzero status.
 *
 * An acquired lock has no expiry. It stays held until the operation finishes or
 * the process terminates, even if a background task continues separately.
 *
 * Callers must keep the lock from loading task state until all operation getters
 * and state changes have finished. Polling commands release it between updates
 * and load a fresh configuration on each iteration.
 */
final class TaskLock
{
    private readonly LockInterface $lock;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lock = $lockFactory->createLock('task.json', null);
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     *
     * @throws TaskBusyException If another process holds the lock
     * @throws \RuntimeException If the lock cannot be acquired or released
     */
    public function synchronized(callable $operation): mixed
    {
        if ($this->lock->isAcquired()) {
            // Nested task operations share the outer operation's lock
            return $operation();
        }

        if (!$this->lock->acquire()) {
            throw new TaskBusyException();
        }

        try {
            return $operation();
        } finally {
            $this->lock->release();
        }
    }
}
