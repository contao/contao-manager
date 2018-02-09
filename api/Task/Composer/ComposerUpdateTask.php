<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractProcessTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class ComposerUpdateTask extends AbstractProcessTask
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
        return (new TaskStatus($this->translator->trans('task.composer_update.title')))
            ->setSummary('Updating Composer dependencies …')
            ->setAudit(true);
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('composer-update');
        } catch (\Exception $e) {
            // do nothing
        }

        $arguments = array_merge(
            [
                'composer',
                'update',
                '--with-dependencies',
                '--no-dev',
                '--prefer-dist',
                '--no-suggest',
                '--no-progress',
                '--no-interaction',
                '--optimize-autoloader',
            ]
        );

        if ($config->getOption('debug', false)) {
            $arguments[] = '--profile';
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess($arguments, 'composer-update');
    }
}
