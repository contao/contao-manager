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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SetupPackagesTask extends AbstractPackagesTask
{
    private static $supportedVersions = ['4.4.*', '4.5.*'];

    /**
     * {@inheritdoc}
     */
    public function update(TaskConfig $config)
    {
        $version = $config->getOption('version');

        if (!in_array($version, static::$supportedVersions)) {
            throw new BadRequestHttpException('Unsupported Contao version');
        }

        $status = new TaskStatus($this->translator->trans('task.setup_packages.title'), true);
        $status->setCancellable(true);

        if ($config->getState('stopping', false) || $this->filesystem->exists($this->rootFiles['json'])) {
            return parent::doUpdate($status, $config);
        }

        if ($this->filesystem->exists($this->rootFiles['vendor'])) {
            throw new BadRequestHttpException('Cannot install into existing application');
        }

        $status->setSummary('Downloading application template …');
        $status->setDetail('contao/managed-edition ' . $version);

        $this->downloadComposerJson($version, $this->rootFiles['json']);

        return $status;
    }

    protected function getProcess(TaskConfig $config)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('packages');
        } catch (\Exception $e) {
            return $this->processFactory->createManagerConsoleBackgroundProcess(
                $this->getInstallProcessArguments(),
                'packages'
            );
        }
    }

    protected function getComposerDefinition(TaskConfig $config)
    {
        return new CloudChanges($this->rootFiles['json']);
    }

    /**
     * @param string $version
     * @param string $targetFile
     */
    private function downloadComposerJson($version, $targetFile)
    {
        $sourceRepo = new CompositeRepository(RepositoryFactory::defaultRepos(new NullIO()));
        $pool = new Pool('stable');
        $pool->addRepository($sourceRepo);
        $selector = new VersionSelector($pool);

        $package = $selector->findBestCandidate('contao/managed-edition', $version);

        if (!$package) {
            throw new \RuntimeException('No valid package to install');
        }

        // TODO use composer remote filesystem

        $this->filesystem->dumpFile(
            $targetFile,
            file_get_contents('https://raw.githubusercontent.com/contao/managed-edition/'.$package->getDistReference().'/composer.json')
        );
    }
}
