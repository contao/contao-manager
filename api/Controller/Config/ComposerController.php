<?php

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
