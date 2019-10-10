<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractTask implements TaskInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var TaskOperationInterface[]
     */
    private $operations;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskConfig $config): TaskStatus
    {
        return (new TaskStatus($this->getTitle()))
            ->setSummary($this->translator->trans('taskstatus.created'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function update(TaskConfig $config): TaskStatus
    {
        if ($config->isCancelled()) {
            return $this->abort($config);
        }

        $status = $this->create($config);
        $status->setStatus(TaskStatus::STATUS_ACTIVE);

        foreach ($this->getOperations($config) as $operation) {
            if (!$operation->isStarted() || $operation->isRunning()) {
                if (null !== $this->logger) {
                    $this->logger->info('Current operation: '.\get_class($operation));
                }

                $operation->run();

                if ($operation->hasError()) {
                    $status->setStatus(TaskStatus::STATUS_ERROR);

                    if (null !== $this->logger) {
                        $this->logger->info('Failed operation: '.\get_class($operation));
                    }
                }

                $operation->updateStatus($status);
                $this->updateStatus($status);

                return $status;
            }

            $operation->updateStatus($status);

            if ($operation->isSuccessful()) {
                if (null !== $this->logger) {
                    $this->logger->info('Completed operation: '.\get_class($operation));
                }

                continue;
            }

            $status->setStatus(TaskStatus::STATUS_ERROR);
            $this->updateStatus($status);

            return $status;
        }

        $status->setStatus(TaskStatus::STATUS_COMPLETE);

        $this->updateStatus($status);

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function abort(TaskConfig $config): TaskStatus
    {
        $config->setCancelled();

        $status = $this->create($config);
        $status->setStatus(TaskStatus::STATUS_STOPPED);

        foreach ($this->getOperations($config) as $operation) {
            $operation->abort();
            $operation->updateStatus($status);

            if ($operation->isRunning()) {
                $status->setStatus(TaskStatus::STATUS_ABORTING);

                if (null !== $this->logger) {
                    $this->logger->info('Task operation is active, aborting', ['class' => \get_class($operation)]);
                }
                break;
            }
        }

        $this->updateStatus($status);

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(TaskConfig $config): bool
    {
        $operations = $this->getOperations($config);

        foreach ($operations as $operation) {
            if ($operation->isRunning()) {
                if (null !== $this->logger) {
                    $this->logger->info('Cannot delete active operation', ['class' => \get_class($operation)]);
                }

                return false;
            }
        }

        foreach ($operations as $operation) {
            $operation->delete();

            if (null !== $this->logger) {
                $this->logger->info('Deleting operation', ['class' => \get_class($operation)]);
            }
        }

        $config->delete();

        return true;
    }

    /**
     * @return TaskOperationInterface[]
     */
    protected function getOperations(TaskConfig $config): array
    {
        if (null === $this->operations) {
            $this->operations = $this->buildOperations($config);

            foreach ($this->operations as $operation) {
                if (null !== $this->logger && $operation instanceof LoggerAwareInterface) {
                    $operation->setLogger($this->logger);
                }
            }
        }

        return $this->operations;
    }

    protected function updateStatus(TaskStatus $status): void
    {
        $result = $status->getStatus();

        if (TaskStatus::STATUS_ACTIVE === $result) {
            return;
        }

        $status->setSummary($this->translator->trans(sprintf('taskstatus.%s.summary', $result)));
        $status->setDetail($this->translator->trans(sprintf('taskstatus.%s.detail', $result)));
        $status->addConsole($this->translator->trans(sprintf('taskstatus.%s.console', $result)), '---');
    }

    abstract protected function getTitle(): string;

    /**
     * @return TaskOperationInterface[]
     */
    abstract protected function buildOperations(TaskConfig $config): array;
}
