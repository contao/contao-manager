<?php

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\ManagerConfig;

class ManagerController extends AbstractConfigController
{
    public function __construct(ManagerConfig $config)
    {
        parent::__construct($config);
    }
}
