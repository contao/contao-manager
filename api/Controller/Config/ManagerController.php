<?php

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\ManagerConfig;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/config/manager", methods={"GET", "PUT", "PATCH"})
 */
class ManagerController extends AbstractConfigController
{
    public function __construct(ManagerConfig $config)
    {
        parent::__construct($config);
    }
}
