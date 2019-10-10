<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;

class SysTempDirCheck extends AbstractIntegrityCheck
{
    public function run(): ?ApiProblem
    {
        if (false !== ($tmpfile = tempnam(sys_get_temp_dir(), '')) && is_writable($tmpfile)) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('systempdir.title'),
            'https://php.net/open_basedir'
        ))->setDetail($this->trans('systempdir.detail'));
    }
}
