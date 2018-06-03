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
