<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Manager;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\System\SelfUpdate;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Manager\SelfUpdateOperation;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;

class SelfUpdateTask extends AbstractTask
{
    /**
     * @var SelfUpdate
     */
    private $updater;

    /**
     * Constructor.
     *
     * @param Translator $translator
     * @param SelfUpdate $updater
     */
    public function __construct(SelfUpdate $updater, Translator $translator)
    {
        $this->updater = $updater;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'manager/self-update';
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskConfig $config)
    {
        return parent::create($config)->setConsole(false);
    }

    protected function getTitle()
    {
        return $this->translator->trans('task.self_update.title');
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskOperationInterface[]
     */
    protected function buildOperations(TaskConfig $config)
    {
        return [
            new SelfUpdateOperation($this->updater, $config, $this->translator),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateStatus(TaskStatus $status)
    {
        if ($status->getStatus() === TaskStatus::STATUS_COMPLETE) {
            $status->setSummary($this->translator->trans('task.self_update.completeSummary'));
            $status->setDetail(
                $this->translator->trans(
                    'task.self_update.completeDetail',
                    [
                        'current' => $this->updater->getOldVersion(),
                    ]
                )
            );
            $status->setConsole(null);

            return;
        }

        parent::updateStatus($status);
    }
}
