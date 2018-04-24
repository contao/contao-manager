<?php

namespace Contao\ManagerApi\Task\Packages;

use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Composer\CloudOperation;
use Contao\ManagerApi\TaskOperation\Composer\CreateProjectOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Symfony\Component\Filesystem\Filesystem;

class SetupTask extends AbstractPackagesTask
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var CloudResolver
     */
    private $cloudResolver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Environment           $environment
     * @param ConsoleProcessFactory $processFactory
     * @param ManagerConfig         $managerConfig
     * @param CloudResolver         $cloudResolver
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(Environment $environment, ConsoleProcessFactory $processFactory, ManagerConfig $managerConfig, CloudResolver $cloudResolver, Translator $translator, Filesystem $filesystem)
    {
        parent::__construct($environment, $managerConfig, $filesystem, $translator);

        $this->environment = $environment;
        $this->processFactory = $processFactory;
        $this->cloudResolver = $cloudResolver;
        $this->filesystem = $filesystem;
    }


    /**
     * @inheritDoc
     */
    protected function createInitialStatus(TaskConfig $config)
    {
        return new TaskStatus($this->translator->trans('task.setup_packages.title'), true);
    }

    /**
     * @inheritDoc
     */
    protected function buildOperations(TaskConfig $config)
    {
        $operations = [new CreateProjectOperation($config, $this->environment, $this->filesystem)];

        if ($this->useCloud()) {
            $operations[] = new CloudOperation(
                $this->cloudResolver,
                new CloudChanges($this->environment->getJsonFile()),
                $config,
                $this->environment,
                $this->filesystem
            );
        }

        $operations[] = new InstallOperation($this->processFactory);

        return $operations;
    }
}
