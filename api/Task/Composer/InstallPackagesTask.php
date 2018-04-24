<?php

namespace Contao\ManagerApi\Task\Composer;

use Composer\Json\JsonFile;
use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class InstallPackagesTask extends AbstractPackagesTask
{
    /**
     * {@inheritdoc}
     */
    public function update(TaskConfig $config)
    {
        $status = new TaskStatus($this->translator->trans('task.install_packages.title'), true);
        $status->setCancellable(true);

        if ((new JsonFile($this->rootFiles['lock']))->exists()) {
            $config->setState('locked', true);
        }

        return parent::doUpdate($status, $config);
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('packages');
        } catch (\Exception $e) {
            // continue
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess(
            $this->getInstallProcessArguments(),
            'packages'
        );
    }

    protected function getComposerDefinition(TaskConfig $config)
    {
        return new CloudChanges($this->rootFiles['json']);
    }
}
