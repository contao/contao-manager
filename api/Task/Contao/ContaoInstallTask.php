<?php

namespace Contao\ManagerApi\Task\Contao;

use Composer\DependencyResolver\Pool;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryFactory;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ContaoInstallTask extends AbstractTask
{
    private static $supportedVersions = ['4.4.*', '4.5.*'];

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(ApiKernel $kernel, ConsoleProcessFactory $processFactory, Translator $translator, Filesystem $filesystem)
    {
        $this->kernel = $kernel;
        $this->processFactory = $processFactory;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function update(TaskConfig $config)
    {
        $status = new TaskStatus($this->translator->trans('task.contao_install.title'), true);
        $version = $config->getOption('version');

        if (!in_array($version, static::$supportedVersions)) {
            throw new BadRequestHttpException('Unsupported Contao version');
        }

        $pInstall = $this->getProcess(
            'composer-install',
            [
                'composer',
                'install',
                '--prefer-dist',
                '--no-dev',
                '--no-progress',
                '--no-suggest',
                '--no-interaction',
                '--optimize-autoloader',
            ]
        );

        $status->setConsole($pInstall->getOutput().$pInstall->getErrorOutput());

        if ('stopping' === $config->getStatus()) {
            if (!$pInstall->isRunning()) {
                $status->setSummary('The task has been cancelled.');
                $status->setStatus(TaskStatus::STATUS_STOPPED);
            } else {
                $status->setSummary('Stopping processes …');

                $pInstall->stop();
            }

        } elseif (!$config->getStatus()) {
            $rootFiles = [
                $this->kernel->getContaoDir() . '/composer.json',
                $this->kernel->getContaoDir() . '/composer.lock',
                $this->kernel->getContaoDir() . '/vendor',
            ];
            if ($this->filesystem->exists($rootFiles)) {
                throw new BadRequestHttpException('Cannot install into existing directory');
            }

            $config->setStatus('installing');
            $status->setSummary('Downloading application template …');
            $status->setDetail('contao/managed-edition '.$version);
            $status->setProgress(20);

            $this->downloadComposerJson($version);

        } elseif (!$pInstall->isTerminated()) {
            $status->setSummary('Installing Composer dependencies …');
            $status->setDetail($pInstall->getCommandLine());
            $status->setProgress(60);

            if (!$pInstall->isStarted()) {
                $pInstall->start();
                $status->setProgress(40);
            }

        } elseif (!$pInstall->isSuccessful()) {

            $status->setSummary('Failed to install Composer dependencies');
            $status->setDetail($pInstall->getCommandLine());
            $status->setStatus(TaskStatus::STATUS_ERROR);

        } else {
            $status->setSummary('Contao installed successfully');
            $status->setProgress(100);
            $status->setStatus(TaskStatus::STATUS_COMPLETE);
        }

        return $status;
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function stop(TaskConfig $config)
    {
        $config->setStatus('stopping');

        return $this->update($config);
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function delete(TaskConfig $config)
    {
        $status = $this->stop($config);

        if (!$status->isActive()) {
            $this->getProcess('composer-install', [])->delete();
        }

        return $status;
    }

    /**
     * @param string $id
     * @param array  $arguments
     *
     * @return \Terminal42\BackgroundProcess\ProcessController
     */
    private function getProcess($id, array $arguments)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess($id);
        } catch (\Exception $e) {
            return $this->processFactory->createManagerConsoleBackgroundProcess($arguments, $id);
        }
    }

    private function downloadComposerJson($version)
    {
        $sourceRepo = new CompositeRepository(RepositoryFactory::defaultRepos(new NullIO()));
        $pool = new Pool('stable');
        $pool->addRepository($sourceRepo);
        $selector = new VersionSelector($pool);

        $package = $selector->findBestCandidate('contao/managed-edition', $version);

        if (!$package) {
            throw new \RuntimeException('No valid package to install');
        }

        $this->filesystem->dumpFile(
            $this->kernel->getContaoDir().'/composer.json',
            file_get_contents('https://raw.githubusercontent.com/contao/managed-edition/'.$package->getDistReference().'/composer.json')
        );
    }
}
