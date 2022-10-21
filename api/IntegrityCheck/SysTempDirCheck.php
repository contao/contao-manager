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
        $tempdir = rtrim(sys_get_temp_dir(), '/');
        $subdir = $tempdir.'/'.md5(__DIR__);

        if (!$this->canWriteFileInDirectory($tempdir) || !$this->canWriteFileInDirectory($subdir, true)) {
            return (new ApiProblem(
                $this->trans('systempdir.title'),
                'https://php.net/open_basedir'
            ))->setDetail($this->trans('systempdir.detail'));
        }

        return null;
    }

    private function canWriteFileInDirectory(string $path, bool $createDirectory = false): bool
    {
        if ($createDirectory) {
            @rmdir($path);
            @mkdir($path);
        }

        $file = $path.'/'.md5(__FILE__);

        $result = touch($file) && is_writable($file);

        @unlink($file);

        if ($createDirectory) {
            @rmdir($path);
        }

        return $result;
    }
}
