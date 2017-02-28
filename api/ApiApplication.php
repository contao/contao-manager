<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Tenside\Core\Util\RuntimeHelper;
use Tenside\CoreBundle\Command\RunTaskCommand;

class ApiApplication extends Application
{
    private $kernel;
    private $commandsRegistered;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;

        parent::__construct($kernel);

        $this->setName('Contao Manager');
        $this->setVersion('@package_version@');
    }

    protected function registerCommands()
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        RuntimeHelper::setupHome($container->get('tenside.home')->homeDir());

        if (\Phar::running()) {
            $command = new RunTaskCommand();
            $command->setContainer($container);

            $this->add($command);

            //$this->setDefaultCommand('tenside:runtask', true);
        } else {
            // Necessary to warmup the cache
            parent::registerCommands();
        }
    }
}
