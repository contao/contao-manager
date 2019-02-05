<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Packages;

use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\System\ServerInfo;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\Composer\CloudOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveVendorOperation;
use Symfony\Component\Filesystem\Filesystem;

class InstallTask extends AbstractPackagesTask
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var CloudResolver
     */
    private $cloudResolver;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param ServerInfo            $serverInfo
     * @param CloudResolver         $cloudResolver
     * @param Environment           $environment
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(ConsoleProcessFactory $processFactory, CloudResolver $cloudResolver, Environment $environment, ServerInfo $serverInfo, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($environment, $serverInfo, $filesystem, $translator);

        $this->processFactory = $processFactory;
        $this->cloudResolver = $cloudResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'composer/install';
    }

    protected function getTitle()
    {
        return $this->translator->trans('task.install_packages.title');
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOperations(TaskConfig $config)
    {
        $changes = new CloudChanges($this->environment->getComposer());
        $changes->setDryRun((bool) $config->getOption('dry_run', false));

        $operations = [];

        if ($config->getOption('remove-vendor', false)) {
            $operations[] = new RemoveVendorOperation($config, $this->environment, $this->translator, $this->filesystem);
        }

        if ($this->environment->useCloudResolver() && !$this->filesystem->exists($this->environment->getLockFile())) {
            $operations[] = new CloudOperation(
                $this->cloudResolver,
                $changes,
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem
            );
        }

        $operations[] = new InstallOperation($this->processFactory, $config, $this->translator, $changes->getDryRun(), $this->getInstallTimeout());

        return $operations;
    }
}
