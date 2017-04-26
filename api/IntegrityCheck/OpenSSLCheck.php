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

class OpenSSLCheck implements IntegrityCheckInterface
{
    public function run()
    {
        if (extension_loaded('openssl')) {
            return null;
        }

        return new ApiProblem(
            'The PHP OpenSSL extension is not available.',
            'https://php.net/openssl'
        );
    }
}
