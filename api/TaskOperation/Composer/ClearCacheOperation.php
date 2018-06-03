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

class ClearCacheOperation extends AbstractProcessOperation
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
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator)
    {
        $this->translator = $translator;

        try {
            parent::__construct($processFactory->restoreBackgroundProcess('clear-cache'));
        } catch (\Exception $e) {
            parent::__construct(
                $processFactory->createManagerConsoleBackgroundProcess(
                    [
                        'composer',
                        'clear-cache',
                        '--no-interaction',
                    ],
                    'clear-cache'
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.clear-cache.summary'));

        $this->addConsoleStatus($status);
    }
}
