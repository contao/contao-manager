<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\Task\TaskManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'task:delete', description: 'Deletes the current task if it is not active.')]
class TaskDeleteCommand extends Command
{
    public function __construct(private readonly TaskManager $taskManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->taskManager->hasTask()) {
            $output->writeln('No task is currently active.');

            return Command::FAILURE;
        }

        $status = $this->taskManager->deleteTask();

        if (null === $status || $status->isActive()) {
            $output->writeln('Task could not be deleted.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
