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
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Contao\ManagerApi\TaskOperation\Composer\UpdateOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\DumpPackagesOperation;
use Symfony\Component\Filesystem\Filesystem;

class UpdateTask extends AbstractPackagesTask
{
    /**
     * @var CloudResolver
     */
    private $cloudResolver;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param CloudResolver         $cloudResolver
     * @param Environment           $environment
     * @param ManagerConfig         $managerConfig
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(ConsoleProcessFactory $processFactory, CloudResolver $cloudResolver, Environment $environment, ManagerConfig $managerConfig, Translator $translator, Filesystem $filesystem)
    {
        parent::__construct($environment, $managerConfig, $filesystem, $translator);

        $this->processFactory = $processFactory;
        $this->cloudResolver = $cloudResolver;
        $this->environment = $environment;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'update_packages';
    }

    /**
     * @inheritDoc
     */
    protected function buildOperations(TaskConfig $config)
    {
        $changes = $this->getComposerDefinition($config);

        if ($this->useCloud()) {
            return [
                new CloudOperation($this->cloudResolver, $changes, $config, $this->environment, $this->filesystem),
                new InstallOperation($this->processFactory, $changes->getDryRun()),
            ];
        }

        return [
            new DumpPackagesOperation($changes, $this->filesystem, $config),
            new UpdateOperation($this->processFactory, $changes->getUpdates(), $changes->getDryRun()),
        ];
    }

    protected function getComposerDefinition(TaskConfig $config)
    {
        $definition = new CloudChanges($this->environment->getJsonFile());

        foreach ($config->getOption('require', []) as $name => $version) {
            $definition->requirePackage($name, $version);
        }

        foreach ($config->getOption('remove', []) as $name) {
            $definition->removePackage($name);
        }

        $definition->setUpdates($config->getOption('update', []));
        $definition->setDryRun($config->getOption('dry_run', false));

        return $definition;
    }
}
