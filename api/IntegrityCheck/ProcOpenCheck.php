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

class ProcOpenCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if (function_exists('proc_open')) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('proc_open.title'),
            'https://php.net/proc_open'
        ))->setDetail($this->trans('proc_open.detail'));
    }
}
