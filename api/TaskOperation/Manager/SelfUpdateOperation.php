<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Manager;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\System\SelfUpdate;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_UPDATE')]
class SelfUpdateOperation extends AbstractInlineOperation
{
    public function __construct(
        private readonly SelfUpdate $updater,
        TaskConfig $taskConfig,
        private readonly Translator $translator,
    ) {
        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return basename(\Phar::running()).' self-update';
    }

    public function getDetails(): string|null
    {
        if ($this->isSuccessful()) {
            return $this->translator->trans(
                'taskoperation.self-update.success',
                ['new' => $this->updater->getOldVersion()],
            );
        }

        return $this->translator->trans(
            'taskoperation.self-update.detail',
            ['old' => $this->updater->getOldVersion(), 'new' => $this->updater->getNewVersion()],
        );
    }

    protected function doRun(): bool
    {
        return $this->updater->update();
    }

    protected function getName(): string
    {
        return 'self-update';
    }
}
