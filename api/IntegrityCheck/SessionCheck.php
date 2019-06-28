<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;

class SessionCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        $detail = '';

        try {
            $options = [
                'read_and_close' => '1',
                'use_cookies' => '0',
            ];

            if (false !== session_start($options)) {
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
