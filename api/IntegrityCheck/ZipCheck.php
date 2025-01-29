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

use Composer\Util\Platform;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Process\ExecutableFinder;

class ZipCheck extends AbstractIntegrityCheck
{
    public function run(): ApiProblem|null
    {
        if (class_exists(\ZipArchive::class)) {
            return null;
        }

        $finder = new ExecutableFinder();

        if (
            $finder->find('unzip')
            || $finder->find('7z', null, ['C:\Program Files\7-Zip'])
            || (!Platform::isWindows() && $finder->find('7zz'))
        ) {
            return null;
        }

        return new ApiProblem(
            $this->trans('zip.title'),
            'https://getcomposer.org/doc/articles/troubleshooting.md#zip-archives-are-not-unpacked-correctly-',
        );
    }
}
