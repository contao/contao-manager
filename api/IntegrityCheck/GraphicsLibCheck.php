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

class GraphicsLibCheck extends AbstractIntegrityCheck
{
    public function run(): ApiProblem|null
    {
        if ($this->hasGraphicsLib()) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('graphics_lib.title'),
            'https://php.net/gd',
        ))->setDetail($this->trans('graphics_lib.detail'));
    }

    private function hasGraphicsLib(): bool
    {
        if (\function_exists('gd_info') && version_compare(\constant('GD_VERSION'), '2.0.1', '>')) {
            return true;
        }

        if (class_exists('Imagick')) {
            return true;
        }

        if (class_exists('Gmagick')) {
            return true;
        }

        return false;
    }
}
