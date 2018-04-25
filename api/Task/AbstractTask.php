<?php

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;

abstract class AbstractTask implements TaskInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var TaskOperationInterface[]
     */
    private $operations;

    /**
     * Constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function update(TaskConfig $config)
    {
        if ($config->isCancelled()) {
            return $this->abort($config);
        }

        $status = $this->createInitialStatus($config);

        foreach ($this->getOperations($config) as $operation) {
            if (!$operation->isStarted() || $operation->isRunning()) {
                $operation->run();

                if ($operation->hasError()) {
                    $status->setStatus(TaskStatus::STATUS_ERROR);
                }

                $operation->updateStatus($status);
                $this->updateStatus($status);

                return $status;
            }

            $operation->updateStatus($status);

            if ($operation->isSuccessful()) {
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
    public function abort(TaskConfig $config)
    {
        $config->setCancelled();

        $status = $this->createInitialStatus($config);
        $status->setStatus(TaskStatus::STATUS_STOPPED);

        foreach ($this->getOperations($config) as $operation) {
            $operation->abort();
            $operation->updateStatus($status);

            if ($operation->isRunning()) {
                $status->setStatus(TaskStatus::STATUS_ABORTING);
                break;
            }

            if ($operation->isSuccessful()) {
                continue;
            }

            break;
        }

        $this->updateStatus($status);

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(TaskConfig $config)
    {
        $status = $this->abort($config);

        if ($status->isStopped()) {
            foreach ($this->getOperations($config) as $operation) {
                $operation->delete();
            }
        }

        return $status;
    }

    protected function getOperations(TaskConfig $config)
    {
        if (null === $this->operations) {
            $this->operations = $this->buildOperations($config);
        }

        return $this->operations;
    }

    /**
     * @param TaskStatus $status
     */
    protected function updateStatus(TaskStatus $status)
    {
        switch ($status->getStatus()) {
            case TaskStatus::STATUS_ACTIVE:
                break;

            case TaskStatus::STATUS_COMPLETE:
                $status->setSummary('Console task complete!');
                $status->setDetail('The background task was completed successfully. Check the console protocol for the details.');
                break;

            case TaskStatus::STATUS_ABORTING:
                $status->setSummary('Stopping current operation …');
                $status->setDetail('The background task is being cancelled.');
                break;

            case TaskStatus::STATUS_STOPPED:
                $status->setSummary('Console task terminated!');
                $status->setDetail('The background task was cancelled. Please check the console protocol.');
                break;

            case TaskStatus::STATUS_ERROR:
                $status->setSummary('Console task terminated!');
                $status->setDetail('The background task has stopped unexpectedly. Please check the console protocol.');
                break;
        }
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    abstract protected function createInitialStatus(TaskConfig $config);

    /**
     * @param TaskConfig $config
     *
     * @return TaskOperationInterface[]
     */
    abstract protected function buildOperations(TaskConfig $config);
}
