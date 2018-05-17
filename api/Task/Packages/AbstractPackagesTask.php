<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
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
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Environment $environment
     * @param Filesystem  $filesystem
     * @param Translator  $translator
     */
    public function __construct(Environment $environment, Filesystem $filesystem, Translator $translator)
    {
        $this->environment = $environment;
        $this->filesystem = $filesystem;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskConfig $config)
    {
        return parent::create($config)->setAudit(!$config->getOption('dry_run', false));
    }

    /**
     * {@inheritdoc}
     */
    public function update(TaskConfig $config)
    {
        $this->createBackup($config);

        $status = parent::update($config);

        $this->restoreBackup($status, $config);

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function abort(TaskConfig $config)
    {
        $status = parent::abort($config);

        $this->restoreBackup($status, $config);

        return $status;
    }

    private function createBackup(TaskConfig $config)
    {
        if (!$config->getState('backup-created', false) && $this->filesystem->exists($this->environment->getJsonFile())) {
            foreach ($this->getBackupPaths() as $source => $target) {
                if ($this->filesystem->exists($source)) {
                    $this->filesystem->copy($source, $target, true);
                }
            }

            $config->setState('backup-created', true);
        }
    }

    private function restoreBackup(TaskStatus $status, TaskConfig $config)
    {
        if (($status->hasError() || $status->isStopped()) && $config->getState('backup-created', false) && !$config->getState('backup-restored', false)) {
            foreach (array_flip($this->getBackupPaths()) as $source => $target) {
                if ($this->filesystem->exists($source)) {
                    $this->filesystem->copy($source, $target, true);
                    $this->filesystem->remove($source);
                }
            }

            $config->setState('backup-restored', true);
        }
    }

    private function getBackupPaths()
    {
        return [
            $this->environment->getJsonFile() => sprintf('%s/%s~', $this->environment->getManagerDir(), basename($this->environment->getJsonFile())),
            $this->environment->getLockFile() => sprintf('%s/%s~', $this->environment->getManagerDir(), basename($this->environment->getLockFile())),
        ];
    }
}
