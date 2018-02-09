<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractProcessTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class DumpAutoloadTask extends AbstractProcessTask
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

    protected function getInitialStatus(TaskConfig $config)
    {
        return (
            new TaskStatus($this->translator->trans('task.dump_autoload.title'))
        )->setSummary('Dumping class autoloader …');
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('dump-autoload');
        } catch (\Exception $e) {
            // do nothing
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess(
            [
                'composer',
                'dump-autoload',
                '--optimize',
            ],
            'dump-autoload'
        );
    }
}
