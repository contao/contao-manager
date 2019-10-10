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

class PhpExtensionsCheck extends AbstractIntegrityCheck
{
    private static $extensions = [
        'intl',
        'dom',
        'xmlreader',
        'openssl',
    ];

    public function run(): ?ApiProblem
    {
        foreach (self::$extensions as $extension) {
            if (null !== ($problem = $this->checkExtension($extension))) {
                return $problem;
            }
        }

        return null;
    }

    private function checkExtension($extension): ?ApiProblem
    {
        if (\extension_loaded($extension)) {
            return null;
        }

        return (new ApiProblem(
            $this->trans($extension.'.title'),
            'https://php.net/'.$extension
        ))->setDetail($this->trans($extension.'.detail'));
    }
}
