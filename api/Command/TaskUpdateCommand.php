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
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TaskUpdateCommand extends Command
{
    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * Constructor.
     */
    public function __construct(TaskManager $taskManager)
    {
        parent::__construct();

        $this->taskManager = $taskManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('task:update')
            ->setDescription('Updates the current task and returns the status information.')
            ->addOption('poll', null, InputOption::VALUE_NONE, 'Poll for updates until the task is completed.')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Poll interval in seconds.', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->taskManager->hasTask()) {
            $output->writeln('No task is currently active.');

            return 1;
        }

        $status = $this->taskManager->updateTask();

        if (null === $status) {
            return 1;
        }

        if (!$output instanceof ConsoleOutput) {
            return 1;
        }

        $style = new SymfonyStyle($input, $output);
        $style->title($status->getTitle());

        $sections = [];
        $progresses = [];
        $operations = $status->getOperations();

        foreach ($operations as $i => $operation) {
            $section = $output->section();
            $section->writeln(($operation->isRunning() ? ' > ' : ' - ').$operation->getSummary());
            $section->writeln('');

            $sections[$i] = $section;
        }

        $this->updateOperations($status->getOperations(), $sections, $progresses);

        if ($input->getOption('poll')) {
            while ($status->isActive()) {
                sleep((int)$input->getOption('interval'));

                $newStatus = $this->taskManager->updateTask();

                if (null === $newStatus) {
                    return 1;
                }

                if ($this->updateOperations($newStatus->getOperations(), $sections, $progresses)) {
                    break;
                }
            }
        }

        switch ($status->getStatus()) {
            case TaskStatus::STATUS_COMPLETE:
                $style->success('Operations completed successfully');
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

    /**
     * @param TaskOperationInterface[] $operations
     * @param ConsoleSectionOutput[] $sections
     * @param ProgressIndicator[] $progresses
     */
    private function updateOperations(array $operations, array $sections, array &$progresses): bool
    {
        foreach ($operations as $i => $operation) {
            $section = $sections[$i];
            $progress = $progresses[$i] ?? null;

            $this->updateOperation($operation, $section, $progress);

            $progresses[$i] = $progress;

            if (!$operation->isStarted() || $operation->isRunning()) {
                return false;
            }
        }

        return true;
    }

    private function updateOperation(TaskOperationInterface $operation, ConsoleSectionOutput $section, ?ProgressIndicator &$progress): void
    {
        if (!$operation->isStarted()) {
            return;
        }

        $section->clear();

        if ($operation->isRunning()) {
            if (!$progress) {
                $progress = new ProgressIndicator($section);
                $progress->start($operation->getSummary());
            }

            $progress->advance();
            $section->writeln('   '.$operation->getDetails());
            $section->writeln('');
            return;
        }

        if ($progress) {
            $progress->finish($operation->getSummary());
            $section->clear();
            $progress = null;
        }

        if ($operation->isSuccessful()) {
            $icon = sprintf(
                '<fg=green;options=bold>%s</>',
                '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" // HEAVY CHECK MARK (U+2714)
            );
        } elseif ($operation->hasError()) {
            $icon = sprintf(
                '<fg=red;options=bold>%s</>',
                '\\' === \DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" // HEAVY BALLOT X (U+2718)
            );
        }

        $section->writeln(' '.$icon.' '.$operation->getSummary());
        $section->writeln('   '.$operation->getDetails());
        $section->writeln('');
    }
}
