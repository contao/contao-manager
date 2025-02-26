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

use Contao\ManagerApi\Config\ComposerConfig;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/config/composer', methods: ['GET', 'PUT', 'PATCH'])]
#[IsGranted('ROLE_INSTALL')]
class ComposerController extends AbstractConfigController
{
    public function __construct(ComposerConfig $config)
    {
        parent::__construct($config);
    }
}
