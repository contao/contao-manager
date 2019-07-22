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

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'composer/update';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
            $operations[] = new InstallOperation($this->processFactory, $config, $this->environment, $this->translator, $changes->getDryRun(), $this->getInstallTimeout());
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

        foreach ($config->getOption('require', []) as $name => $version) {
            $definition->requirePackage($name, $version);
        }

        foreach ($config->getOption('remove', []) as $name) {
            $definition->removePackage($name);
        }

        $this->addContaoConflictsRequirement($definition);
        $definition->setUpdates($config->getOption('update', []));
        $definition->setDryRun($config->getOption('dry_run', false));

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateStatus(TaskStatus $status): void
    {
        if (TaskStatus::STATUS_COMPLETE === $status->getStatus()) {
            $status->setSummary($this->translator->trans('task.update_packages.completeSummary'));
            $status->setDetail($this->translator->trans('task.update_packages.completeDetail'));
        }

        parent::updateStatus($status);
    }

    private function addContaoConflictsRequirement(CloudChanges $definition): void
    {
        $rootPackage = $this->environment->getComposer()->getPackage();

        foreach ($rootPackage->getRequires() as $package => $constraint) {
            if ('contao/conflicts' === $package && '*@dev' === $constraint->getPrettyConstraint()) {
                return;
            }
        }

        $definition->requirePackage('contao/conflicts', '*@dev');
    }
}
