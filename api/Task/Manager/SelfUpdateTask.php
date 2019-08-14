<?php

declare(strict_types=1);

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

    public function __construct(SelfUpdate $updater, Translator $translator)
    {
        $this->updater = $updater;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'manager/self-update';
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskConfig $config): TaskStatus
    {
        return parent::create($config)->setConsole(false);
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.self_update.title');
    }

    /**
     * @return TaskOperationInterface[]
     */
    protected function buildOperations(TaskConfig $config): array
    {
        return [
            new SelfUpdateOperation($this->updater, $config, $this->translator),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateStatus(TaskStatus $status): void
    {
        if (TaskStatus::STATUS_COMPLETE === $status->getStatus()) {
            $status->setSummary($this->translator->trans('task.self_update.completeSummary'));
            $status->setDetail(
                $this->translator->trans(
                    'task.self_update.completeDetail',
                    [
                        'current' => $this->updater->getOldVersion(),
                    ]
                )
            );
            $status->setConsole(false);

            return;
        }

        parent::updateStatus($status);
    }
}
