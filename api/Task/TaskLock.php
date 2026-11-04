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
 * Locks the stable sidecar file, never the atomically replaced task.json inode.
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
     * @throws \RuntimeException If the lock cannot be acquired or released
     */
    public function synchronized(callable $operation): mixed
    {
        if ($this->lock->isAcquired()) {
            return $operation();
        }

        if (!$this->lock->acquire(true)) {
            throw new \RuntimeException('Cannot acquire task lock.');
        }

        try {
            return $operation();
        } finally {
            $this->lock->release();
        }
    }
}
