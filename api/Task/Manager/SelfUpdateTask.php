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
use Contao\ManagerApi\TaskOperation\Manager\SelfUpdateOperation;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;

class SelfUpdateTask extends AbstractTask
{
    public function __construct(
        private readonly SelfUpdate $updater,
        Translator $translator,
    ) {
        parent::__construct($translator);
    }

    public function getName(): string
    {
        return 'manager/self-update';
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.self_update.title');
    }

    /**
     * @return array<TaskOperationInterface>
     */
    protected function buildOperations(TaskConfig $config): array
    {
        return [
            new SelfUpdateOperation($this->updater, $config, $this->translator),
        ];
    }
}
