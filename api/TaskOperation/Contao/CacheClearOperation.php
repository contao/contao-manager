<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class CacheClearOperation extends AbstractProcessOperation
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param string                $environment
     * @param string                $processId
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator, $environment, $processId = 'cache-clear')
    {
        $this->translator = $translator;

        try {
            parent::__construct($processFactory->restoreBackgroundProcess($processId));
        } catch (\Exception $e) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['cache:clear', '--env='.$environment, '--no-warmup'], $processId));
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.cache-clear.summary'));

        $this->addConsoleStatus($status);
    }
}
