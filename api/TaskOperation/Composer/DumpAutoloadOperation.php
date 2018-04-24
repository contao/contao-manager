<?php

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class DumpAutoloadOperation extends AbstractProcessOperation
{

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     */
    public function __construct(ConsoleProcessFactory $processFactory)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('dump-autoload'));
        } catch (\Exception $e) {
            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    [
                        'composer',
                        'dump-autoload',
                        '--optimize',
                    ],
                    'dump-autoload'
                )
            );
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Dumping class autoloader …');

        $this->addConsoleStatus($status);
    }
}
