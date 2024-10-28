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
use Contao\ManagerApi\TaskOperation\Contao\BackupRestoreOperation;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class BackupRestoreTask extends AbstractTask
{
    public function __construct(private readonly ConsoleProcessFactory $processFactory, Translator $translator)
    {
        parent::__construct($translator);
    }

    public function getName(): string
    {
        return 'contao/backup-restore';
    }

    public function create(TaskConfig $config): TaskStatus
    {
        return parent::create($config);
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.backup_restore.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        $file = $config->getOption('file');
        $backup = $config->getOption('backup', false);

        if (!$file) {
            throw new BadRequestException();
        }

        $operations = [
            new BackupRestoreOperation($this->processFactory, $file),
        ];

        if ($backup) {
            array_unshift($operations, new BackupCreateOperation($this->processFactory));
        }

        return $operations;
    }
}
