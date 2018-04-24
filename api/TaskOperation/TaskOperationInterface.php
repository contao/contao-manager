<?php

namespace Contao\ManagerApi\TaskOperation;

use Contao\ManagerApi\Task\TaskStatus;

interface TaskOperationInterface
{
    public function isStarted();

    public function isRunning();

    public function isSuccessful();

    public function run();

    public function abort();

    public function delete();

    public function updateStatus(TaskStatus $status);
}
