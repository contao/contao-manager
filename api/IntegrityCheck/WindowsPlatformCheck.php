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
use Symfony\Component\HttpFoundation\Response;

class WindowsPlatformCheck implements IntegrityCheckInterface
{
    public function run()
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            return null;
        }

        return (new ApiProblem(
            'This version of Contao Manager is currently not supported on Windows.',
            'https://github.com/contao/contao-manager/issues/66'
        ))->setStatus(Response::HTTP_NOT_IMPLEMENTED);
    }
}
