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

class AllowUrlFopenCheck implements IntegrityCheckInterface
{
    public function run()
    {
        if (ini_get('allow_url_fopen')) {
            return null;
        }

        return new ApiProblem(
            'The PHP setting "allow_url_fopen" is not enabled on the server.',
            'https://php.net/allow_url_fopen'
        );
    }
}
