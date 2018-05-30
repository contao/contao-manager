<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\TaskOperation\Manager;

use Contao\ManagerApi\I18n\Translator;
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
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param Updater    $updater
     * @param TaskConfig $taskConfig
     * @param Translator $translator
     */
    public function __construct(Updater $updater, TaskConfig $taskConfig, Translator $translator)
    {
        $this->updater = $updater;
        $this->translator = $translator;

        parent::__construct($taskConfig);
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
            ->setSummary($this->translator->trans('taskoperation.self-update.summary'))
            ->setDetail(
                $this->translator->trans(
                    'taskoperation.self-update.detail',
                    ['old' => $this->updater->getOldVersion(), 'new' => $this->updater->getNewVersion()]
                )
            )
        ;

        $this->addConsoleStatus($status);
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'self-update';
    }
}
