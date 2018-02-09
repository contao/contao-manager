<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractProcessTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class ComposerInstallTask extends AbstractProcessTask
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
    }

    protected function getInitialStatus(TaskConfig $config)
    {
        return (
            new TaskStatus($this->translator->trans('task.composer_install.title'))
        )->setSummary('Installing Composer dependencies …');
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('composer-install');
        } catch (\Exception $e) {
            // do nothing
        }

        $arguments = [
            'composer',
            'install',
            '--prefer-dist',
            '--no-dev',
            '--no-progress',
            '--no-suggest',
            '--no-interaction',
            '--optimize-autoloader',
        ];

        if ($config->getOption('debug', false)) {
            $arguments[] = '--profile';
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess($arguments, 'composer-install');
    }
}
