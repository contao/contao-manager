<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractProcessTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class ComposerRemoveTask extends AbstractProcessTask
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
        return (new TaskStatus($this->translator->trans('task.composer_remove.title')))
            ->setSummary('Removing Composer dependencies …')
            ->setAudit(true);
    }

    protected function getProcess(TaskConfig $config)
    {
        $packages = $config->getOption('packages');

        if (!is_array($packages)) {
            throw new \InvalidArgumentException('Missing list of packages.');
        }

        try {
            return $this->processFactory->restoreBackgroundProcess('composer-remove');
        } catch (\Exception $e) {
            // do nothing
        }

        $arguments = array_merge(
            [
                'composer',
                'remove',
            ],
            $packages,
            [
                '--update-no-dev',
                '--no-progress',
                '--no-interaction',
                '--optimize-autoloader',
            ]
        );

        if ($config->getOption('debug', false)) {
            $arguments[] = '--profile';
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess($arguments, 'composer-remove');
    }
}
