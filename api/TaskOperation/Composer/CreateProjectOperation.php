<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Composer\DependencyResolver\Pool;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Util\RemoteFilesystem;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class CreateProjectOperation extends AbstractInlineOperation
{
    /**
     * @var array
     */
    private static $supportedVersions = ['4.4.*', '4.6.*'];

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $version;

    /**
     * Constructor.
     *
     * @param TaskConfig  $taskConfig
     * @param Environment $environment
     * @param Translator  $translator
     * @param Filesystem  $filesystem
     */
    public function __construct(TaskConfig $taskConfig, Environment $environment, Translator $translator, Filesystem $filesystem)
    {
        parent::__construct($taskConfig);

        $this->environment = $environment;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
        $this->version = $taskConfig->getOption('version');

        if (!in_array($this->version, static::$supportedVersions, true)) {
            throw new \InvalidArgumentException('Unsupported Contao version');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.create-project.summary'));
        $status->setDetail('contao/managed-edition '.$this->version);

        $this->addConsoleStatus($status);
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'create-project';
    }

    /**
     * {@inheritdoc}
     */
    protected function doRun()
    {
        if ($this->filesystem->exists($this->environment->getAll())) {
            throw new \RuntimeException('Cannot install into existing application');
        }

        $sourceRepo = new CompositeRepository(RepositoryFactory::defaultRepos(new NullIO()));
        $pool = new Pool('stable');
        $pool->addRepository($sourceRepo);
        $selector = new VersionSelector($pool);
        $phpVersion = sprintf('%s.%s.%s', PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION);

        $package = $selector->findBestCandidate('contao/managed-edition', $this->version, $phpVersion);

        if (!$package) {
            throw new \RuntimeException('No valid package to install');
        }

        $remoteFilesystem = new RemoteFilesystem(new NullIO());

        $this->filesystem->dumpFile(
            $this->environment->getJsonFile(),
            $remoteFilesystem->getContents('raw.githubusercontent.com', 'https://raw.githubusercontent.com/contao/managed-edition/'.$package->getDistReference().'/composer.json')
        );

        return true;
    }
}
