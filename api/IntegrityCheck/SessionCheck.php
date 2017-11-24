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

class SessionCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        $detail = '';

        try {
            if (false !== session_start()) {
                return null;
            }
        } catch (\ErrorException $exception) {
            $detail = $exception->getMessage();
        }

        return (new ApiProblem(
            $this->trans('session.title'),
            'https://php.net/session_start'
        ))->setDetail($detail);
    }
}
