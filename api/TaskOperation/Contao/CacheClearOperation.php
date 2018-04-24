<?php

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class CacheClearOperation extends AbstractProcessOperation
{
    /**
     * Constructor.
     */
    public function __construct(ConsoleProcessFactory $processFactory)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('cache-clear'));
        } catch (\Exception $e) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['cache:clear', '--no-warmup'], 'cache-clear'));
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        parent::updateStatus($status);

        $status->setSummary('Clearing application cache …');
        $status->setDetail($this->process->getCommandLine());
    }
}
