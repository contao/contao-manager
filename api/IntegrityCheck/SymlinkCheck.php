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

class SymlinkCheck extends AbstractIntegrityCheck implements CliIntegrityCheckInterface
{
    public function run()
    {
        if ($this->canCreateSymlinks()) {
            return null;
        }

        return new ApiProblem(
            $this->trans('symlink.title'),
            'https://php.net/symlink'
        );
    }

    private function canCreateSymlinks()
    {
        if (!function_exists('symlink')) {
            return false;
        }

        $tmpfile = tempnam(sys_get_temp_dir(), '');

        @unlink($tmpfile);
        $result = @symlink(__FILE__, $tmpfile);
        @unlink($tmpfile);

        return true === $result;
    }
}
