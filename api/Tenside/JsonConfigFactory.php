<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside;

use Tenside\Core\Config\TensideJsonConfig;
use Tenside\Core\Util\HomePathDeterminator as TensideHomePathDeterminator;
use Tenside\Core\Util\JsonFile;
use Tenside\CoreBundle\DependencyInjection\Factory\TensideJsonConfigFactory;

class JsonConfigFactory extends TensideJsonConfigFactory
{
    public static function create(TensideHomePathDeterminator $home)
    {
        return new TensideJsonConfig(new JsonFile($home->tensideDataDir().DIRECTORY_SEPARATOR.'manager.json'));
    }
}
