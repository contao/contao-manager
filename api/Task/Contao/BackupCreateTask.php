<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Contao;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Contao\BackupCreateOperation;

class BackupCreateTask extends AbstractTask
{
    public function __construct(
        private readonly ConsoleProcessFactory $processFactory,
        Translator $translator,
    ) {
        parent::__construct($translator);
    }

    public function getName(): string
    {
        return 'contao/backup-create';
    }

    public function create(TaskConfig $config): TaskStatus
    {
        return parent::create($config)->setAutoClose(true);
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.backup_create.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        return [
            new BackupCreateOperation($this->processFactory),
        ];
    }
}
