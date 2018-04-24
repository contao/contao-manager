<?php

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class CacheWarmupOperation extends AbstractProcessOperation
{
    /**
     * Constructor.
     */
    public function __construct(ConsoleProcessFactory $processFactory)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('cache-warmup'));
        } catch (\Exception $e) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['cache:warmup'], 'cache-warmup'));
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        parent::updateStatus($status);

        $status->setSummary('Warming application cache …');
        $status->setDetail($this->process->getCommandLine());
    }
}
