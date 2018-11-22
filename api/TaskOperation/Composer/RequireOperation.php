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

class RequireOperation extends AbstractProcessOperation
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
     * @param array                 $required
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator, array $required)
    {
        $this->translator = $translator;

        try {
            $process = $processFactory->restoreBackgroundProcess('composer-require');

            parent::__construct($process);
        } catch (\Exception $e) {
            $arguments = array_merge(
                [
                    'composer',
                    'require',
                ],
                $required,
                [
                    '--no-suggest',
                    '--no-update',
                    '--no-scripts',
                    '--prefer-stable',
                    '--sort-packages',
                    '--no-ansi',
                    '--no-interaction',
                ]
            );

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-require'
            );

            parent::__construct($process);
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.composer-require.summary'));

        $status->setDetail($this->process->getCommandLine());

        $this->addConsoleStatus($status);
    }
}
