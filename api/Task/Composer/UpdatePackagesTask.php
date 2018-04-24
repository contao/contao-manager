<?php

namespace Contao\ManagerApi\Task\Composer;

use Composer\DependencyResolver\Pool;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryFactory;
use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;

class UpdatePackagesTask extends AbstractPackagesTask
{
    /**
     * {@inheritdoc}
     */
    public function update(TaskConfig $config)
    {
        $status = new TaskStatus($this->translator->trans('task.update_packages.title'));
        $status->setCancellable(true);
        $status->setAudit(!$config->getOption('dry_run', false));

        return parent::doUpdate($status, $config);
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('packages');
        } catch (\Exception $e) {
            // continue
        }

        if (!$this->config->get('disable_cloud', false)) {
            return $this->processFactory->createManagerConsoleBackgroundProcess(
                $this->getInstallProcessArguments($config->getOption('dry_run', false)),
                'packages'
            );
        }

        return $this->processFactory->createManagerConsoleBackgroundProcess(
            $this->getUpdateProcessArguments($config->getOption('update', []), $config->getOption('dry_run', false)),
            'packages'
        );
    }

    protected function getComposerDefinition(TaskConfig $config)
    {
        $definition = new CloudChanges($this->rootFiles['json']);

        foreach ($config->getOption('require', []) as $name => $version) {
            $definition->requirePackage($name, $version);
        }

        foreach ($config->getOption('remove', []) as $name) {
            $definition->removePackage($name);
        }

        $definition->setUpdates($config->getOption('update', []));
        $definition->setDryRun($config->getOption('dry_run', false));

        return $definition;
    }
}
