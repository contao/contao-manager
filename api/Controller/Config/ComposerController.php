<?php

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\ComposerConfig;

class ComposerController extends AbstractConfigController
{
    public function __construct(ComposerConfig $config)
    {
        parent::__construct($config);
    }
}
