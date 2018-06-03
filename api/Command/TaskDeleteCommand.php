<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\Task\TaskManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskDeleteCommand extends Command
{
    /**
     * @var TaskManager
     */
    private $taskManager;

    /**
     * Constructor.
     *
     * @param TaskManager $taskManager
     */
    public function __construct(TaskManager $taskManager)
    {
        parent::__construct();

        $this->taskManager = $taskManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('task:delete')
            ->setDescription('Deletes the current task if it is not active.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->taskManager->hasTask()) {
            $output->writeln('No task is currently active.');

            return 1;
        }

        $status = $this->taskManager->deleteTask();

        if (null === $status || $status->isActive()) {
            $output->writeln('Task could not be deleted.');

            return 1;
        }

        return 0;
    }
}
