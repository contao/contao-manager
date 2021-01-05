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

use Composer\Semver\VersionParser;
use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\System\ServerInfo;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Composer\CloudOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Contao\ManagerApi\TaskOperation\Composer\RemoveOperation;
use Contao\ManagerApi\TaskOperation\Composer\RequireOperation;
use Contao\ManagerApi\TaskOperation\Composer\UpdateOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\InstallUploadsOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveUploadsOperation;
use Symfony\Component\Filesystem\Filesystem;

class UpdateTask extends AbstractPackagesTask
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
     * @var UploadsConfig
     */
    private $uploads;

    public function __construct(ConsoleProcessFactory $processFactory, CloudResolver $cloudResolver, UploadsConfig $uploads, Environment $environment, ServerInfo $serverInfo, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($environment, $serverInfo, $filesystem, $translator);

        $this->processFactory = $processFactory;
        $this->cloudResolver = $cloudResolver;
        $this->uploads = $uploads;
    }

    public function getName(): string
    {
        return 'composer/update';
    }

    public function update(TaskConfig $config): TaskStatus
    {
        $status = parent::update($config);

        if ($status->isComplete() && $config->getOption('dry_run', false)) {
            $this->restoreState($config);
        }

        return $status;
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.update_packages.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        $changes = $this->getComposerDefinition($config);

        $operations = [];

        if (($required = $changes->getRequiredPackages()) && !empty($required)) {
            $operations[] = new RequireOperation($this->processFactory, $this->translator, $required);
        }

        if (($removed = $changes->getRemovedPackages()) && !empty($removed)) {
            $operations[] = new RemoveOperation($this->processFactory, $this->translator, $removed);
        }

        if ($this->environment->useCloudResolver()) {
            $operations[] = new CloudOperation($this->cloudResolver, $changes, $config, $this->environment, $this->translator, $this->filesystem);
            $operations[] = new InstallOperation($this->processFactory, $config, $this->environment, $this->translator, $changes->getDryRun());
        } else {
            $operations[] = new UpdateOperation($this->processFactory, $this->environment, $this->translator, $changes->getUpdates(), $changes->getDryRun());
        }

        if ($config->getOption('uploads', false) && \count($this->uploads)) {
            $uploads = array_filter(
                $this->uploads->all(),
                function ($upload) use ($changes) {
                    return $upload['success']
                        && isset($upload['package']['name'])
                        && \in_array($upload['package']['name'], $changes->getUpdates(), true);
                }
            );

            array_unshift($operations, new InstallUploadsOperation(
                $uploads,
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem
            ));

            if (!$config->getOption('dry_run', false)) {
                $operations[] = new RemoveUploadsOperation(
                    $uploads,
                    $this->uploads,
                    $config,
                    $this->environment,
                    $this->translator,
                    $this->filesystem
                );
            }
        }

        return $operations;
    }

    protected function getComposerDefinition(TaskConfig $config): CloudChanges
    {
        $definition = new CloudChanges();
        $definition->setUpdates($config->getOption('update', []));
        $definition->setDryRun($config->getOption('dry_run', false));

        foreach ($config->getOption('require', []) as $name => $version) {
            $definition->requirePackage($name, $version);
        }

        foreach ($config->getOption('remove', []) as $name) {
            $definition->removePackage($name);
        }

        $this->addContaoConflictsRequirement($definition);
        $this->handleContaoStability($definition);

        return $definition;
    }

    private function addContaoConflictsRequirement(CloudChanges $definition): void
    {
        $rootRequires = $this->environment->getComposer()->getPackage()->getRequires();

        if (isset($rootRequires['contao/conflicts'])
            && '*@dev' === $rootRequires['contao/conflicts']->getPrettyConstraint()
        ) {
            if (!empty($definition->getUpdates())) {
                $definition->addUpdate('contao/conflicts');
            }

            return;
        }

        $definition->requirePackage('contao/conflicts', '*@dev');
    }

    private function handleContaoStability(CloudChanges $definition): void
    {
        foreach ($definition->getRequiredPackages() as $require) {
            $require = explode('=', $require, 2);
            $packageName = $require[0];
            $version = $require[1] ?? null;

            // Automatically require core-bundle and installation-bundle if the manager-bundle is not stable
            // otherwise the dependency would not be resolved because we don't set minimum-stability
            if ('contao/manager-bundle' === $packageName) {
                $rootRequires = $this->environment->getComposer()->getPackage()->getRequires();

                if ($version && 'stable' !== VersionParser::parseStability($version)) {
                    if (!isset($rootRequires['contao/core-bundle'])) {
                        $definition->requirePackage('contao/core-bundle', $version);
                    }
                    if (!isset($rootRequires['contao/installation-bundle'])) {
                        $definition->requirePackage('contao/installation-bundle', $version);
                    }
                } else {
                    if (isset($rootRequires['contao/core-bundle'])) {
                        $definition->removePackage('contao/core-bundle');
                    }
                    if (isset($rootRequires['contao/installation-bundle'])) {
                        $definition->removePackage('contao/installation-bundle');
                    }
                }

                return;
            }
        }

        // Automatically update the core-bundle and installation-bundle when updating Contao
        // (but only if they are actually installed, like not on the initial installation)
        foreach ($definition->getUpdates() as $packageName) {
            if ('contao/manager-bundle' === $packageName) {
                $localRepository = $this->environment->getComposer()->getRepositoryManager()->getLocalRepository();

                if (!empty($localRepository->findPackages('contao/core-bundle'))) {
                    $definition->addUpdate('contao/core-bundle');
                }

                if (!empty($localRepository->findPackages('contao/installation-bundle'))) {
                    $definition->addUpdate('contao/installation-bundle');
                }
            }
        }
    }
}
