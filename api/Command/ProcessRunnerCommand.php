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

use Contao\ManagerApi\Process\ProcessRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'background-task:run', description: 'Execute a background task')]
class ProcessRunnerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'Absolute path to the task config file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $process = new ProcessRunner($input->getArgument('path'));

        try {
            $process->run();
        } catch (\Exception $exception) {
            $process->addOutput((string) $exception);
            $process->stop();

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
