<?php

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class UpdateOperation extends AbstractProcessOperation
{
    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param bool                  $dryRun
     */
    public function __construct(ConsoleProcessFactory $processFactory, array $packages = [], $dryRun = false)
    {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('composer-update'));
        } catch (\Exception $e) {
            $arguments = array_merge(
                [
                    'composer',
                    'update',
                ],
                $packages,
                [
                    '--with-dependencies',
                    '--prefer-dist',
                    '--no-dev',
                    '--no-progress',
                    '--no-suggest',
                    '--no-interaction',
                    '--optimize-autoloader',
                ]
            );

            if ($dryRun) {
                $arguments[] = '--dry-run';
            }

            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    $arguments,
                    'composer-update'
                )
            );
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Updating Composer dependencies …');
        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
