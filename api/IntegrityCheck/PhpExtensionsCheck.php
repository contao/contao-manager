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

class PhpExtensionsCheck extends AbstractIntegrityCheck implements CliIntegrityCheckInterface
{
    private $extensions = [
        'intl',
        'dom',
        'xmlreader',
        'openssl',
    ];

    public function run()
    {
        foreach ($this->extensions as $extension) {
            if (($problem = $this->checkExtension($extension)) !== null) {
                return $problem;
            }
        }

        return null;
    }

    private function checkExtension($extension)
    {
        if (extension_loaded('intl')) {
            return null;
        }

        return (new ApiProblem(
            $this->trans($extension.'.title'),
            'https://php.net/'.$extension
        ))->setDetail($this->trans($extension.'.detail'));
    }
}
