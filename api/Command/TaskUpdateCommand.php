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
use Contao\ManagerApi\Task\TaskStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TaskUpdateCommand extends Command
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
            ->setName('task:update')
            ->setDescription('Updates the current task and returns the status information.')
            ->addOption('poll', null, InputOption::VALUE_NONE, 'Poll for updates until the task is completed.')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Poll interval in seconds.', 1)
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

        $status = $this->taskManager->updateTask();

        if (null === $status) {
            return 1;
        }

        $style = new SymfonyStyle($input, $output);
        $style->title($status->getTitle());

        if (!$input->getOption('poll')) {
            $style->text($status->getSummary());
        } else {
            $progress = new ProgressIndicator($output);
            $progress->start($status->getSummary());

            while ($status->isActive()) {
                sleep((int) $input->getOption('interval'));

                $newStatus = $this->taskManager->updateTask();

                if (null === $newStatus) {
                    return 1;
                }

                $progress->advance();

                if ($status->getSummary() !== $newStatus->getSummary()) {
                    $progress->setMessage($newStatus->getSummary());
                }

                $status = $newStatus;
            }

            $progress->finish($status->getSummary());
            $output->writeln('');
        }

        switch ($status->getStatus()) {
            case TaskStatus::STATUS_COMPLETE:
                $style->success('Task completed sucessfully');
                break;

            case TaskStatus::STATUS_ERROR:
                $style->error('Task terminated unexpectedly');
                break;

            case TaskStatus::STATUS_STOPPED:
                $style->warning('Task has been stopped');
                break;
        }

        return 0;
    }
}
