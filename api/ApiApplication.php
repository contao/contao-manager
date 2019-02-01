<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi;

use Contao\ManagerApi\Command\AboutCommand;
use Contao\ManagerApi\Command\IntegrityCheckCommand;
use Contao\ManagerApi\Command\ProcessRunnerCommand;
use Contao\ManagerApi\Command\TaskAbortCommand;
use Contao\ManagerApi\Command\TaskDeleteCommand;
use Contao\ManagerApi\Command\TaskUpdateCommand;
use Contao\ManagerApi\Command\UpdateCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        parent::__construct('Contao Manager', $kernel->getVersion());

        $this->getDefinition()->addOption(new InputOption('disable-events', null, InputOption::VALUE_NONE, 'Disables the event dispatcher.'));
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return ApiKernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->registerCommands();

        if ('self-update' !== $this->getCommandName($input)
            && !$input->hasParameterOption(['--disable-events'], true)
        ) {
            $this->setDispatcher($this->kernel->getContainer()->get('event_dispatcher'));
        }

        return parent::doRun($input, $output);
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
    private function registerCommands()
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        $this->add($container->get(ProcessRunnerCommand::class)->setName('run'));
        $this->add($container->get(AboutCommand::class));
        $this->add($container->get(IntegrityCheckCommand::class));
        $this->add($container->get(TaskAbortCommand::class));
        $this->add($container->get(TaskDeleteCommand::class));
        $this->add($container->get(TaskUpdateCommand::class));
        $this->add($container->get(UpdateCommand::class));

        if (!\Phar::running(false)) {
            $command = new CacheWarmupCommand();
            $command->setContainer($container);
            $this->add($command);
        }
    }
}
