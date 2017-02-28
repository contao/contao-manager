<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside;

use Tenside\Core\Util\HomePathDeterminator as BaseHomePathDeterminator;

class HomePathDeterminator extends BaseHomePathDeterminator
{
    public function homeDir()
    {
        try {
            return parent::homeDir();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(
                'Contao Manager must be placed in the "web" directory',
                $e->getCode(),
                $e
            );
        }
    }

    public function tensideDataDir()
    {
        return $this->homeDir().DIRECTORY_SEPARATOR.'contao-manager';
    }
}
