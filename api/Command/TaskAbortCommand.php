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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'task:abort', description: 'Aborts the current task and returns the status information.')]
class TaskAbortCommand extends TaskUpdateCommand
{
    protected function configure(): void
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->taskManager->hasTask()) {
            $output->writeln('No task is currently active.');

            return Command::FAILURE;
        }

        $this->taskManager->abortTask();

        return parent::execute($input, $output);
    }
}
