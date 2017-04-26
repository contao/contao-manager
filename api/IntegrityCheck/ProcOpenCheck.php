<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;

class ProcOpenCheck implements IntegrityCheckInterface
{
    public function run()
    {
        if (function_exists('proc_open')) {
            return null;
        }

        return new ApiProblem(
            'The PHP function "proc_open" is not available on the server.',
            'https://php.net/proc_open'
        );
    }
}
