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

interface TaskInterface
{
    /**
     * Gets the task name.
     */
    public function getName(): string;

    /**
     * Creates a task.
     */
    public function create(TaskConfig $config): TaskStatus;

    /**
     * Updates the task.
     */
    public function update(TaskConfig $config): TaskStatus;

    /**
     * Cancels a task.
     */
    public function abort(TaskConfig $config): TaskStatus;

    /**
     * Deletes a task.
     */
    public function delete(TaskConfig $config): bool;
}
