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
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/config/composer", methods={"GET", "PUT", "PATCH"})
 */
class ComposerController extends AbstractConfigController
{
    public function __construct(ComposerConfig $config)
    {
        parent::__construct($config);
    }
}
