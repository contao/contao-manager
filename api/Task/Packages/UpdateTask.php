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

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\Composer\CloudOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Contao\ManagerApi\TaskOperation\Composer\RemoveOperation;
use Contao\ManagerApi\TaskOperation\Composer\RequireOperation;
use Contao\ManagerApi\TaskOperation\Composer\UpdateOperation;
use Contao\ManagerApi\TaskOperation\Contao\MaintenanceModeOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\InstallUploadsOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveArtifactsOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveUploadsOperation;
use Symfony\Component\Filesystem\Filesystem;

class UpdateTask extends AbstractPackagesTask
{
    public function __construct(
        private readonly ContaoConsole $contaoConsole,
        private readonly ConsoleProcessFactory $processFactory,
        private readonly CloudResolver $cloudResolver,
        private readonly UploadsConfig $uploads,
        private readonly ApiKernel $kernel,
        Environment $environment,
        Filesystem $filesystem,
        Translator $translator,
    ) {
        parent::__construct($environment, $filesystem, $translator);
    }

    public function getName(): string
    {
        return 'composer/update';
    }

    public function update(TaskConfig $config, bool $continue = false): TaskStatus
    {
        $status = parent::update($config, $continue);

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
        $toggleMaintenance = !$changes->getDryRun() && \array_key_exists('contao:maintenance-mode', $this->contaoConsole->getCommandList());

        $operations = [];

        if (($required = $changes->getRequiredPackages()) && [] !== $required) {
            $operations[] = new RequireOperation($this->processFactory, $required);
        }

        if (($removed = $changes->getRemovedPackages()) && [] !== $removed) {
            $operations[] = new RemoveOperation($this->processFactory, $removed);
        }

        if ($this->environment->useCloudResolver()) {
            $operations[] = new CloudOperation($this->cloudResolver, $changes, $config, $this->environment, $this->translator, $this->filesystem, $this->logger);
        } else {
            $operations[] = new UpdateOperation($this->processFactory, $this->environment, $changes->getUpdates(), $changes->getDryRun());
        }

        if ($toggleMaintenance) {
            $operations[] = new MaintenanceModeOperation($config, $this->processFactory, 'enable');
        }

        $operations[] = new InstallOperation($this->processFactory, $config, $this->environment, $this->translator, $changes->getDryRun(), !$config->isCancelled());

        if ($toggleMaintenance) {
            $operations[] = new MaintenanceModeOperation($config, $this->processFactory, 'disable');
        }

        if ($config->getOption('uploads', false) && \count($this->uploads)) {
            $uploads = array_filter(
                $this->uploads->all(),
                static fn ($upload): bool => $upload['success']
                    && isset($upload['package']['name'])
                    && (
                        [] === $changes->getUpdates()
                        || \in_array($upload['package']['name'], $changes->getUpdates(), true)
                    ),
            );

            array_unshift($operations, new InstallUploadsOperation(
                $uploads,
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem,
            ));

            if (!$config->getOption('dry_run', false)) {
                $operations[] = new RemoveUploadsOperation(
                    $uploads,
                    $this->uploads,
                    $config,
                    $this->environment,
                    $this->translator,
                    $this->filesystem,
                );
            }
        }

        if ($removed) {
            $artifacts = array_filter($this->environment->getArtifacts(), static function (string $file) use ($removed) {
                foreach ($removed as $packageName) {
                    if (str_starts_with($file, str_replace('/', '__', $packageName))) {
                        return true;
                    }
                }

                return false;
            });

            if ([] !== $artifacts) {
                $operations[] = new RemoveArtifactsOperation(
                    $artifacts,
                    $config,
                    $this->environment,
                    $this->translator,
                    $this->filesystem,
                );
            }
        }

        return $operations;
    }

    protected function getComposerDefinition(TaskConfig $config): CloudChanges
    {
        $updates = $config->getOption('update', []);

        $definition = new CloudChanges();
        $definition->setUpdates($updates);
        $definition->setDryRun($config->getOption('dry_run', false));

        foreach ($config->getOption('require', []) as $name => $version) {
            $definition->requirePackage($name, $version);
        }

        foreach ($config->getOption('remove', []) as $name) {
            $definition->removePackage($name);
        }

        $this->addContaoConflictsRequirement($definition);
        $this->handleContaoRequirement($definition);

        // Update all packages if none are set
        if (empty($updates)) {
            $definition->setUpdates([]);
        }

        return $definition;
    }

    private function addContaoConflictsRequirement(CloudChanges $definition): void
    {
        $rootRequires = $this->environment->getComposer()->getPackage()->getRequires();

        if (
            isset($rootRequires['contao/conflicts'])
            && '*@dev' === $rootRequires['contao/conflicts']->getPrettyConstraint()
        ) {
            if ([] !== $definition->getUpdates()) {
                $definition->addUpdate('contao/conflicts');
            }

            return;
        }

        $definition->requirePackage('contao/conflicts', '*@dev');
    }

    private function handleContaoRequirement(CloudChanges $definition): void
    {
        foreach ($definition->getRequiredPackages() as $require) {
            $require = explode('=', (string) $require, 2);
            $packageName = $require[0];
            $version = $require[1] ?? null;

            // Automatically require core-bundle and installation-bundle if the
            // manager-bundle is not stable otherwise the dependency would not be resolved
            // because we don't set minimum-stability
            if ('contao/manager-bundle' === $packageName && null !== $version) {
                $rootRequires = $this->environment->getComposer()->getPackage()->getRequires();

                $versionParser = new VersionParser();
                $constraint = $versionParser->parseConstraints($version);
                $isContao5 = $constraint->matches(new Constraint('>=', '5@dev'));

                // Patch composer.json to make sure we have a valid public-dir and install scripts
                if ($isContao5) {
                    try {
                        $jsonFile = $this->environment->getComposerJsonFile();
                        $json = $jsonFile->read();

                        if (!isset($json['extra']['public-dir'])) {
                            $json['extra']['public-dir'] = basename($this->kernel->getPublicDir());
                        }

                        foreach (['post-install-cmd', 'post-update-cmd'] as $group) {
                            if (isset($json['scripts'][$group]) && \is_array($json['scripts'][$group])) {
                                foreach ($json['scripts'][$group] as $k => $script) {
                                    if ('Contao\ManagerBundle\Composer\ScriptHandler::initializeApplication' === $script) {
                                        $json['scripts'][$group][$k] = '@php vendor/bin/contao-setup';
                                        break;
                                    }
                                }
                            } elseif (isset($json['scripts'][$group]) && 'Contao\ManagerBundle\Composer\ScriptHandler::initializeApplication' === $json['scripts'][$group]) {
                                $json['scripts'][$group] = '@php vendor/bin/contao-setup';
                            }
                        }

                        $jsonFile->write($json);
                    } catch (\Exception) {
                        // Ignore
                    }
                }

                if ('stable' !== VersionParser::parseStability($version)) {
                    $definition->requirePackage('contao/core-bundle', $version);

                    if (!$isContao5) {
                        $definition->requirePackage('contao/installation-bundle', $version);
                    } elseif (isset($rootRequires['contao/installation-bundle'])) {
                        $definition->removePackage('contao/installation-bundle');
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
