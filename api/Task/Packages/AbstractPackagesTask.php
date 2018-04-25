<?php

namespace Contao\ManagerApi\Task\Packages;

use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\ManagerConfig;
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
    private $environment;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Environment   $environment
     * @param ManagerConfig $managerConfig
     * @param Filesystem    $filesystem
     * @param Translator    $translator
     */
    public function __construct(Environment $environment, ManagerConfig $managerConfig, Filesystem $filesystem, Translator $translator)
    {
        $this->environment = $environment;
        $this->managerConfig = $managerConfig;
        $this->filesystem = $filesystem;

        parent::__construct($translator);
    }

    /**
     * @inheritDoc
     */
    public function update(TaskConfig $config)
    {
        if (!$config->getState('backup-created', false) && $this->filesystem->exists($this->environment->getJsonFile())) {
            $this->filesystem->copy($this->environment->getJsonFile(), $this->environment->getJsonFile().'~', true);

            if ($this->filesystem->exists($this->environment->getLockFile())) {
                $this->filesystem->copy(
                    $this->environment->getLockFile(),
                    $this->environment->getLockFile() . '~',
                    true
                );
            }

            $config->setState('backup-created', true);
        }

        $status = parent::update($config);

        $this->restoreBackup($status, $config);

        return $status;
    }

    /**
     * @inheritDoc
     */
    public function abort(TaskConfig $config)
    {
        $status = parent::abort($config);

        $this->restoreBackup($status, $config);

        return $status;
    }

    /**
     * @return bool
     */
    protected function useCloud()
    {
        return false;

        // TODO enable cloud features
        return !$this->managerConfig->get('disable_cloud', false);
    }

    private function restoreBackup(TaskStatus $status, TaskConfig $config)
    {
        if (($status->hasError() || $status->isStopped()) && $config->getState('backup-created', false) && !$config->getState('backup-restored', false)) {
            if ($this->filesystem->exists($this->environment->getJsonFile().'~')) {
                $this->filesystem->copy(
                    $this->environment->getJsonFile() . '~',
                    $this->environment->getJsonFile(),
                    true
                );
                $this->filesystem->remove($this->environment->getJsonFile() . '~');
            }

            if ($this->filesystem->exists($this->environment->getLockFile().'~')) {
                $this->filesystem->copy(
                    $this->environment->getLockFile() . '~',
                    $this->environment->getLockFile(),
                    true
                );
                $this->filesystem->remove($this->environment->getLockFile() . '~');
            }

            $config->setState('backup-restored', true);
        }
    }
}
