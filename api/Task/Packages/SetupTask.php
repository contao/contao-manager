<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Packages;

use Contao\ManagerApi\ApiKernel;
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
     * @var ApiKernel
     */
    private $kernel;

    public function __construct(ConsoleProcessFactory $processFactory, CloudResolver $cloudResolver, Environment $environment, ApiKernel $kernel, ServerInfo $serverInfo, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($environment, $serverInfo, $filesystem, $translator);

        $this->processFactory = $processFactory;
        $this->cloudResolver = $cloudResolver;
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'contao/install';
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.setup_packages.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        $operations = [new CreateProjectOperation($config, $this->environment, $this->kernel, $this->filesystem)];

        if ($config->getOption('no-update')) {
            return $operations;
        }

        if ($this->environment->useCloudResolver()) {
            $operations[] = new CloudOperation(
                $this->cloudResolver,
                new CloudChanges(),
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem
            );
        }

        $operations[] = new InstallOperation($this->processFactory, $config, $this->environment, $this->translator, false, !$config->isCancelled());

        return $operations;
    }
}
