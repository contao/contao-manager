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

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskBusyException extends ServiceUnavailableHttpException
{
    public function __construct()
    {
        parent::__construct(1, 'Task management is busy. Please retry.');
    }
}
