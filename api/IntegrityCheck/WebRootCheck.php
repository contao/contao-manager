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

class WebRootCheck implements IntegrityCheckInterface
{
    public function run()
    {
        if (!($phar = \Phar::running()) || 'web' === basename(dirname($phar))) {
            return null;
        }

        return (new ApiProblem(
            'The Phar file must be located in the /web folder.'/*,
            'https://github.com/contao/contao-manager/issues/66'*/
        ))->setStatus(Response::HTTP_NOT_IMPLEMENTED);
    }
}
