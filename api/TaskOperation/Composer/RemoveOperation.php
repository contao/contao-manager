<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;

class RemoveOperation extends AbstractProcessOperation
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
     * @param array                 $removed
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator, array $removed)
    {
        $this->translator = $translator;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-remove');

            parent::__construct($process);
        } catch (\Exception $e) {
            $arguments = array_merge(
                [
                    'composer',
                    'remove',
                ],
                $removed,
                [
                    '--no-update',
                    '--no-scripts',
                    '--no-ansi',
                    '--no-interaction',
                ]
            );

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-remove'
            );

            parent::__construct($process);
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.composer-remove.summary'));

        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
