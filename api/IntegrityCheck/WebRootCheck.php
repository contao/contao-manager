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
use Symfony\Component\HttpFoundation\Response;

class WebRootCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if (!($phar = \Phar::running()) || 'web' === basename(dirname($phar))) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('web_root.title')
        ))->setStatus(Response::HTTP_NOT_IMPLEMENTED);
    }
}
