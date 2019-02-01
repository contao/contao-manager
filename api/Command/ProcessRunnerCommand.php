<?php

namespace Contao\ManagerApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Contao\ManagerApi\Process\ProcessRunner;

class ProcessRunnerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('background-task:run')
            ->setDescription('Execute a background task')
            ->addArgument('path', InputArgument::REQUIRED, 'Absolute path to the task config file.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $process = new ProcessRunner($input->getArgument('path'));

        try {
            $process->run();
        } catch (\Exception $e) {
            $process->addErrorOutput((string) $e);
            $process->stop();
        }
    }
}
