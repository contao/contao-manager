<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Composer\ClearCacheOperation;

class ClearCacheTask extends AbstractTask
{
    public function __construct(
        private readonly ConsoleProcessFactory $processFactory,
        Translator $translator,
    ) {
        parent::__construct($translator);
    }

    public function getName(): string
    {
        return 'composer/clear-cache';
    }

    public function create(TaskConfig $config): TaskStatus
    {
        return parent::create($config)->setAutoClose(true);
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.clear_cache.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        return [
            new ClearCacheOperation($this->processFactory),
        ];
    }
}
