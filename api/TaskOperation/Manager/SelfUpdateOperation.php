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

class SelfUpdateOperation extends AbstractInlineOperation
{
    /**
     * @var SelfUpdate
     */
    private $updater;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(SelfUpdate $updater, TaskConfig $taskConfig, Translator $translator)
    {
        $this->updater = $updater;
        $this->translator = $translator;

        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return basename(\Phar::running()).' self-update';
    }

    public function getDetails(): ?string
    {
        return $this->translator->trans(
            'taskoperation.self-update.detail',
            ['old' => $this->updater->getOldVersion(), 'new' => $this->updater->getNewVersion()]
        );
    }

    protected function doRun(): bool
    {
        return $this->updater->update();
    }

    /**
     * {@inheritdoc}
     */
    protected function getName(): string
    {
        return 'self-update';
    }
}
