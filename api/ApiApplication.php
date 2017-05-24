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
use Symfony\Component\Console\Command\Command;
use Tenside\CoreBundle\Command\RunTaskCommand;
use Terminal42\BackgroundProcess\Command\ProcessRunnerCommand;

class ApiApplication extends Application
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var bool
     */
    private $commandsRegistered = false;

    /**
     * Constructor.
     *
     * @param ApiKernel $kernel
     */
    public function __construct(ApiKernel $kernel)
    {
        $this->kernel = $kernel;

        parent::__construct($kernel);

        $this->setName('Contao Manager');
        $this->setVersion('@package_version@');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = (new Command('composer'))->setDescription('Run Composer within Contao Manager');

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerCommands()
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        if (\Phar::running(false)) {
            $command = new RunTaskCommand();
            $command->setContainer($container);

            $this->add($command);
            $this->add(new ProcessRunnerCommand());
            $this->add($container->get('contao_manager.command.integrity_check'));
        } else {
            parent::registerCommands();
        }
    }
}
