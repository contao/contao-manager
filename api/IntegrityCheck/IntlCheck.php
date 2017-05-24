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

class IntlCheck extends AbstractIntegrityCheck
{
    public function run()
    {
        if (extension_loaded('intl')) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('intl.title'),
            'https://php.net/intl'
        ))->setDetail($this->trans('intl.detail'));
    }
}
