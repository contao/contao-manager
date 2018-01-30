<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;

class ProcessCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if (function_exists('proc_open') && function_exists('proc_close')) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('process.title'),
            'https://php.net/proc_open'
        ))->setDetail($this->trans('process.detail'));
    }
}
