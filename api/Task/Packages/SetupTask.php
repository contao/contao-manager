<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
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
use Contao\ManagerApi\TaskOperation\Composer\CreateProjectOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Symfony\Component\Filesystem\Filesystem;

class SetupTask extends AbstractPackagesTask
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param CloudResolver         $cloudResolver
     * @param Environment           $environment
     * @param ServerInfo            $serverInfo
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(ConsoleProcessFactory $processFactory, CloudResolver $cloudResolver, Environment $environment, ServerInfo $serverInfo, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($environment, $serverInfo, $filesystem, $translator);

        $this->processFactory = $processFactory;
        $this->cloudResolver = $cloudResolver;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'setup_packages';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOperations(TaskConfig $config)
    {
        $operations = [new CreateProjectOperation($config, $this->environment, $this->filesystem)];

        if ($this->environment->useCloudResolver()) {
            $operations[] = new CloudOperation(
                $this->cloudResolver,
                new CloudChanges($this->environment->getJsonFile()),
                $config,
                $this->environment,
                $this->filesystem
            );
        }

        $operations[] = new InstallOperation($this->processFactory, $config, false, $this->getInstallTimeout());

        return $operations;
    }
}
