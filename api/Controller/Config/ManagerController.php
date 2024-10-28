<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\ManagerConfig;
use Symfony\Component\Routing\Annotation\Route;

#[\Symfony\Component\Routing\Attribute\Route(path: '/config/manager', methods: ['GET', 'PUT', 'PATCH'])]
class ManagerController extends AbstractConfigController
{
    public function __construct(ManagerConfig $config)
    {
        parent::__construct($config);
    }
}
