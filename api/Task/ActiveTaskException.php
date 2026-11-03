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

class ActiveTaskException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Active task cannot be deleted');
    }
}
