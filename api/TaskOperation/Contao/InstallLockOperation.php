<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Contao;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class InstallLockOperation extends AbstractProcessOperation
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
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator, $processId = 'install-lock')
    {
        $this->translator = $translator;

        try {
            parent::__construct($processFactory->restoreBackgroundProcess($processId));
        } catch (\Exception $e) {
            parent::__construct($processFactory->createContaoConsoleBackgroundProcess(['contao:install:lock'], $processId));
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.install-lock.summary'));

        $this->addConsoleStatus($status);
    }
}
