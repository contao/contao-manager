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

class TaskConflictException extends \RuntimeException
{
    public function __construct(\Throwable|null $previous = null)
    {
        parent::__construct('The current task does not match the expected task ID.', 0, $previous);
    }
}
