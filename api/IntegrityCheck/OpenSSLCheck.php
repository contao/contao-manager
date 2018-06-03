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

class OpenSSLCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if (extension_loaded('openssl')) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('openssl.title'),
            'https://php.net/openssl'
        ))->setDetail($this->trans('openssl.detail'));
    }
}
