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

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function create(TaskConfig $config): TaskStatus
    {
        $operations = $this->buildOperations($config);

        foreach ($operations as $operation) {
            if (null !== $this->logger && $operation instanceof LoggerAwareInterface) {
                $operation->setLogger($this->logger);
            }
        }

        return new TaskStatus($config->getId(), $this->getTitle(), $operations);
    }

    public function update(TaskConfig $config): TaskStatus
    {
        if ($config->isCancelled()) {
            return $this->abort($config);
        }

        $status = $this->create($config);

        foreach ($status->getOperations() as $operation) {
            if (!$operation->isStarted() || $operation->isRunning()) {
                if (null !== $this->logger) {
                    $this->logger->info('Current operation: '.\get_class($operation));
                }

                $operation->run();

                if (null !== $this->logger && $operation->hasError()) {
                    $this->logger->info('Failed operation: '.\get_class($operation));
                }

                return $status;
            }

            if ($operation->isSuccessful()) {
                if (null !== $this->logger) {
                    $this->logger->info('Completed operation: '.\get_class($operation));
                }

                continue;
            }

            return $status;
        }

        return $status;
    }

    public function abort(TaskConfig $config): TaskStatus
    {
        $config->setCancelled();
        $status = $this->create($config)->setAborted();

        foreach ($status->getOperations() as $operation) {
            $operation->abort();

            if ($operation->isRunning()) {
                if (null !== $this->logger) {
                    $this->logger->info('Task operation is active, aborting', ['class' => \get_class($operation)]);
                }
                break;
            }
        }

        return $status;
    }

    public function delete(TaskConfig $config): bool
    {
        $status = $this->create($config);
        $operations = $status->getOperations();

        foreach ($operations as $operation) {
            if ($operation->isRunning()) {
                if (null !== $this->logger) {
                    $this->logger->info('Cannot delete active operation', ['class' => \get_class($operation)]);
                }

                return false;
            }
        }

        foreach ($operations as $operation) {
            if (null !== $this->logger) {
                $this->logger->info('Deleting operation', ['class' => \get_class($operation)]);
            }

            $operation->delete();
        }

        $config->delete();

        return true;
    }

    abstract protected function getTitle(): string;

    /**
     * @return array<TaskOperationInterface>
     */
    abstract protected function buildOperations(TaskConfig $config): array;
}
