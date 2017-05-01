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

class WindowsPlatformCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('windows.title'),
            'https://github.com/contao/contao-manager/issues/66'
        ))->setStatus(Response::HTTP_NOT_IMPLEMENTED);
    }
}
