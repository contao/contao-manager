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
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\Composer\CloudOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Contao\ManagerApi\TaskOperation\Contao\MaintenanceModeOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveVendorOperation;
use Symfony\Component\Filesystem\Filesystem;

class InstallTask extends AbstractPackagesTask
{
    public function __construct(private readonly ContaoConsole $contaoConsole, private readonly ConsoleProcessFactory $processFactory, private readonly CloudResolver $cloudResolver, Environment $environment, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($environment, $filesystem, $translator);
    }

    public function getName(): string
    {
        return 'composer/install';
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.install_packages.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        $operations = [];
        $dryRun = (bool) $config->getOption('dry_run', false);

        $supportsMaintenance = $config->getState('supports-maintenance');

        if (null === $supportsMaintenance) {
            $supportsMaintenance = \array_key_exists('contao:maintenance-mode', $this->contaoConsole->getCommandList());
            $config->setState('supports-maintenance', $supportsMaintenance);
        }

        if ($config->getOption('remove-vendor', false)) {
            $operations[] = new RemoveVendorOperation($config, $this->environment, $this->filesystem);
        }

        if ($this->environment->useCloudResolver() && !$this->filesystem->exists($this->environment->getLockFile())) {
            $changes = new CloudChanges();
            $changes->setDryRun($dryRun);

            $operations[] = new CloudOperation(
                $this->cloudResolver,
                $changes,
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem
            );
        }

        if ($supportsMaintenance) {
            $operations[] = new MaintenanceModeOperation($config, $this->processFactory, 'enable');
        }

        $operations[] = new InstallOperation($this->processFactory, $config, $this->environment, $this->translator, $dryRun, !$config->isCancelled());

        if ($supportsMaintenance) {
            $operations[] = new MaintenanceModeOperation($config, $this->processFactory, 'disable');
        }

        return $operations;
    }
}
