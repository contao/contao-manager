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

use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractPackagesTask extends AbstractTask
{
    public function __construct(
        protected Environment $environment,
        protected Filesystem $filesystem,
        Translator $translator,
    ) {
        parent::__construct($translator);
    }

    public function create(TaskConfig $config): TaskStatus
    {
        return parent::create($config)->setAudit(!$config->getOption('dry_run', false))->setCancellable(true);
    }

    public function update(TaskConfig $config, bool $continue = false): TaskStatus
    {
        $this->createBackup($config);

        $status = parent::update($config, $continue);

        if ($status->hasError() || $status->isStopped()) {
            $this->restoreState($config);
        }

        return $status;
    }

    public function abort(TaskConfig $config): TaskStatus
    {
        $status = parent::abort($config);

        if ($status->hasError() || $status->isStopped()) {
            $this->restoreState($config);
        }

        return $status;
    }

    /**
     * Creates a backup of the composer.json and composer.lock file and stores the
     * currently installed artifacts.
     */
    protected function createBackup(TaskConfig $config): void
    {
        if ($config->getState('backup-created', false)) {
            return;
        }

        if (!$this->environment->createBackup()) {
            return;
        }

        $config->setState('backup-artifacts', $this->environment->getArtifacts());
        $config->setState('backup-created', true);
    }

    /**
     * Restores the backup files if a backup was created within this task.
     */
    protected function restoreState(TaskConfig $config): void
    {
        if ($config->getState('backup-created', false) && !$config->getState('backup-restored', false)) {
            if (!$this->environment->restoreBackup()) {
                return;
            }

            if (null !== ($previous = $config->getState('backup-artifacts'))) {
                foreach (array_diff($this->environment->getArtifacts(), $previous) as $delete) {
                    $this->filesystem->remove($this->environment->getArtifactDir().'/'.$delete);
                }
            }

            $config->setState('backup-restored', true);
        }
    }
}
