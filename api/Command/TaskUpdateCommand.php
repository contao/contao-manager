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

use Contao\ManagerApi\Task\TaskLock;
use Contao\ManagerApi\Task\TaskManager;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'task:update', description: 'Updates the current task and returns the status information.')]
class TaskUpdateCommand extends Command
{
    /**
     * @var array<ConsoleSectionOutput>
     */
    private array $sections = [];

    /**
     * @var array<ProgressIndicator>
     */
    private array $progresses = [];

    public function __construct(
        protected TaskManager $taskManager,
        protected readonly TaskLock $taskLock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('poll', null, InputOption::VALUE_NONE, 'Poll for updates until the task is completed.')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Poll interval in seconds.', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutput) {
            return Command::FAILURE;
        }

        $this->sections = [];
        $this->progresses = [];
        $style = new SymfonyStyle($input, $output);
        $status = $this->updateStatus($style, $output);

        if (null === $status) {
            $output->writeln('No task is currently active.');

            return Command::FAILURE;
        }

        if ($input->getOption('poll')) {
            while (TaskStatus::STATUS_ACTIVE === $status) {
                sleep((int) $input->getOption('interval'));

                $status = $this->updateStatus($style, $output);

                if (null === $status) {
                    return Command::FAILURE;
                }
            }
        }

        match ($status) {
            TaskStatus::STATUS_COMPLETE => $style->success('Operations completed successfully'),
            TaskStatus::STATUS_ERROR => $style->error('Task terminated unexpectedly'),
            TaskStatus::STATUS_STOPPED => $style->warning('Task has been stopped'),
            default => Command::SUCCESS,
        };

        return Command::SUCCESS;
    }

    private function updateStatus(SymfonyStyle $style, ConsoleOutput $output): string|null
    {
        return $this->taskLock->synchronized(
            function () use ($style, $output): string|null {
                $status = $this->taskManager->updateTask();

                if (null === $status) {
                    return null;
                }

                if ([] === $this->sections) {
                    $style->title($status->getTitle());

                    foreach ($status->getOperations() as $i => $operation) {
                        $section = $output->section();
                        $section->writeln(($operation->isRunning() ? ' > ' : ' - ').$operation->getSummary());
                        $section->writeln('');

                        $this->sections[$i] = $section;
                    }
                }

                $this->updateOperations($status->getOperations(), $this->sections, $this->progresses);

                return $status->getStatus();
            },
        );
    }

    /**
     * @param array<TaskOperationInterface> $operations
     * @param array<ConsoleSectionOutput>   $sections
     * @param array<ProgressIndicator>      $progresses
     */
    private function updateOperations(array $operations, array $sections, array &$progresses): void
    {
        foreach ($operations as $i => $operation) {
            $section = $sections[$i];
            $progress = $progresses[$i] ?? null;

            $this->updateOperation($operation, $section, $progress);

            if ($progress) {
                $progresses[$i] = $progress;
            }

            if (!$operation->isStarted() || $operation->isRunning()) {
                return;
            }
        }
    }

    private function updateOperation(TaskOperationInterface $operation, ConsoleSectionOutput $section, ProgressIndicator|null &$progress): void
    {
        if (!$operation->isStarted()) {
            return;
        }

        $section->clear();

        if ($operation->isRunning()) {
            if (null === $progress) {
                $progress = new ProgressIndicator($section);
                $progress->start($operation->getSummary());
            }

            $progress->advance();
            $section->writeln('   '.$operation->getDetails());
            $section->writeln('');

            return;
        }

        if (null !== $progress) {
            $progress->finish($operation->getSummary());
            $section->clear();
            $progress = null;
        }

        $icon = '';

        if ($operation->isSuccessful()) {
            $icon = \sprintf(
                '<fg=green;options=bold>%s</>',
                '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94", // HEAVY CHECK MARK (U+2714)
            );
        } elseif ($operation->hasError()) {
            $icon = \sprintf(
                '<fg=red;options=bold>%s</>',
                '\\' === \DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98", // HEAVY BALLOT X (U+2718)
            );
        }

        $section->writeln(' '.$icon.' '.$operation->getSummary());
        $section->writeln('   '.$operation->getDetails());
        $section->writeln('');
    }
}
