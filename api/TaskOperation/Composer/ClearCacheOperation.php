<?php

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class ClearCacheOperation extends AbstractProcessOperation
{

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     */
    public function __construct(ConsoleProcessFactory $processFactory)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('clear-cache'));
        } catch (\Exception $e) {
            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    [
                        'composer',
                        'clear-cache',
                        '--no-interaction',
                    ],
                    'clear-cache'
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Deleting cache files …');

        $this->addConsoleStatus($status);
    }
}
