<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Task\TaskStatus;

interface TaskOperationInterface
{
    public function isStarted();

    public function isRunning();

    public function isSuccessful();

    public function hasError();

    public function run();

    public function abort();

    public function delete();

    public function updateStatus(TaskStatus $status);
}
