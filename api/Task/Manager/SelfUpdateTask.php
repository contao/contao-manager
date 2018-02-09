<?php

namespace Contao\ManagerApi\Task\Manager;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\SelfUpdate\Updater;
use Contao\ManagerApi\Task\AbstractProcessTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\ProcessController;

class SelfUpdateTask extends AbstractProcessTask
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
     * @var Updater
     */
    private $updater;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param Updater               $updater
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator, Updater $updater)
    {
        $this->processFactory = $processFactory;
        $this->translator = $translator;
        $this->updater = $updater;

        parent::__construct($translator);
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    protected function getInitialStatus(TaskConfig $config)
    {
        return (new TaskStatus($this->translator->trans('task.self_update.title')))
            ->setStoppable(false)
            ->setSummary('Installing latest Contao Manager …')
            ->setDetail(
                sprintf('Updating from %s to %s', $this->updater->getOldVersion(), $this->updater->getNewVersion())
            );
    }

    /**
     * @return Process|ProcessController
     */
    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('self-update');
        } catch (\Exception $e) {
            // do nothing
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess(['self-update'], 'self-update');
    }
}
