<?php

namespace Contao\ManagerApi\TaskOperation\Manager;

use Contao\ManagerApi\SelfUpdate\Updater;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;

class SelfUpdateOperation extends AbstractInlineOperation
{
    /**
     * @var Updater
     */
    private $updater;

    /**
     * Constructor.
     *
     * @param Updater    $updater
     * @param TaskConfig $taskConfig
     */
    public function __construct(Updater $updater, TaskConfig $taskConfig)
    {
        $this->updater = $updater;

        parent::__construct($taskConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'self-update';
    }

    /**
     * {@inheritdoc}
     */
    public function doRun()
    {
        return $this->updater->update();
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status)
    {
        $status
            ->setSummary('Installing latest Contao Manager …')
            ->setDetail(
                sprintf('Updating from %s to %s', $this->updater->getOldVersion(), $this->updater->getNewVersion())
            )
        ;

        $this->addConsoleStatus($status);
    }
}
