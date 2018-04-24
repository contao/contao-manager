<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractProcessTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class ClearCacheTask extends AbstractProcessTask
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

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
        $this->processFactory = $processFactory;
        $this->translator = $translator;

        parent::__construct($translator);
    }

    protected function createInitialStatus(TaskConfig $config)
    {
        return (
            new TaskStatus($this->translator->trans('task.clear_cache.title'))
        )->setSummary('Deleting cache files …');
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('clear-cache');
        } catch (\Exception $e) {
            // do nothing
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess(
            [
                'composer',
                'clear-cache',
                '--no-interaction',
            ],
            'clear-cache'
        );
    }
}
