<?php

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class DumpPackagesOperation extends AbstractInlineOperation
{
    /**
     * @var CloudChanges
     */
    private $changes;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param CloudChanges $changes
     * @param Filesystem   $filesystem
     */
    public function __construct(CloudChanges $changes, Filesystem $filesystem, TaskConfig $taskConfig)
    {
        $this->changes = $changes;
        $this->filesystem = $filesystem;

        parent::__construct($taskConfig);
    }

    protected function getName()
    {
        return 'dump-packages';
    }

    protected function doRun()
    {
        $this->changes->getJsonFile()->write($this->changes->getJson());
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Updating composer.json …');
    }
}
