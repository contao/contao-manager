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

class AllowUrlFopenCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if (ini_get('allow_url_fopen')) {
            return null;
        }

        return new ApiProblem(
            $this->trans('allow_url_fopen.title'),
            'https://php.net/allow_url_fopen'
        );
    }
}
