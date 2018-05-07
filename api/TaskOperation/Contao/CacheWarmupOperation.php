<?php

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class CacheWarmupOperation extends AbstractProcessOperation
{
    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param string                $environment
     * @param string                $processId
     */
    public function __construct(ConsoleProcessFactory $processFactory, $environment, $processId = 'cache-warmup')
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess($processId));
        } catch (\Exception $e) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['cache:warmup', '--env='.$environment], $processId));
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Warming application cache …');

        $this->addConsoleStatus($status);
    }
}
