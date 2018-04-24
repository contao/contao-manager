<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\Task\TaskManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskAbortCommand extends TaskUpdateCommand
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
        parent::__construct($taskManager);

        $this->taskManager = $taskManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('task:abort')
            ->setDescription('Aborts the current task and returns the status information.')
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

        $this->taskManager->abortTask();

        return parent::execute($input, $output);
    }
}
