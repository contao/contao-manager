<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Composer\DumpAutoloadOperation;

class DumpAutoloadTask extends AbstractTask
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator)
    {
        $this->processFactory = $processFactory;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createInitialStatus(TaskConfig $config)
    {
        return new TaskStatus($this->translator->trans('task.dump_autoload.title'));
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOperations(TaskConfig $config)
    {
        return [
            new DumpAutoloadOperation($this->processFactory),
        ];
    }
}
