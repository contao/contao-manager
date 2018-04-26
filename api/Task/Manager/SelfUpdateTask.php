<?php

namespace Contao\ManagerApi\Task\Manager;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\SelfUpdate\Updater;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Manager\SelfUpdateOperation;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;

class SelfUpdateTask extends AbstractTask
{
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
    public function __construct(Updater $updater, Translator $translator)
    {
        $this->updater = $updater;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'self_update';
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskOperationInterface[]
     */
    protected function buildOperations(TaskConfig $config)
    {
        return [
            new SelfUpdateOperation($this->updater, $config),
        ];
    }
}
