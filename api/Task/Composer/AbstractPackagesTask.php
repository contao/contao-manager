<?php

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Composer\CloudJob;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Symfony\Component\Filesystem\Filesystem;
use Terminal42\BackgroundProcess\ProcessController;

abstract class AbstractPackagesTask extends AbstractTask
{
    /**
     * @var array
     */
    protected $rootFiles;

    /**
     * @var ApiKernel
     */
    protected $kernel;

    /**
     * @var ManagerConfig
     */
    protected $config;

    /**
     * @var ConsoleProcessFactory
     */
    protected $processFactory;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ManagerConfig         $config
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(
        ApiKernel $kernel,
        ManagerConfig $config,
        ConsoleProcessFactory $processFactory,
        Translator $translator,
        Filesystem $filesystem
    ) {
        $this->kernel = $kernel;
        $this->config = $config;
        $this->processFactory = $processFactory;
        $this->translator = $translator;
        $this->filesystem = $filesystem;

        $this->rootFiles = [
            'json' => $this->kernel->getContaoDir() . '/composer.json',
            'lock' => $this->kernel->getContaoDir() . '/composer.lock',
            'vendor' => $this->kernel->getContaoDir() . '/vendor',
        ];
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function doUpdate(TaskStatus $status, TaskConfig $config)
    {
        $process = $this->getProcess($config);

        $status->setConsole($process->getOutput().$process->getErrorOutput());

        if ($config->getState('stopping', false)) {
            if (!$process->isRunning()) {
                $status->setSummary('The task has been cancelled.');
                $status->setStatus(TaskStatus::STATUS_STOPPED);

                if ($this->config->get('disable_cloud', false)) {
                    $this->getComposerDefinition($config)->restoreBackup();
                }
            } else {
                $status->setSummary('Stopping processes …');

                $process->stop();
            }

        } elseif (!$config->getState('locked', false)) {
            $definition = $this->getComposerDefinition($config);

            if ($this->config->get('disable_cloud', false)) {

                $this->getComposerDefinition($config)->createBackup();
                $definition->getJsonFile()->write($definition->getJson());
                $config->setState('locked', true);
                $status->setSummary('Updating composer.json …');

            } else {

                $cloud = new CloudResolver();
                $jobId = $config->getState('cloud-job');

                if (!$jobId) {
                    $job = $cloud->createJob($definition);
                    $config->setState('cloud-job', $job->getId());
                } else {
                    $job = $cloud->getJob($jobId);
                }

                switch ($job->getStatus()) {
                    case CloudJob::STATUS_QUEUED:
                        $status->setSummary('Job queued in Composer Cloud');
                        $status->setDetail(
                            sprintf(
                                'Starting in approx. %s seconds (currently %s jobs on %s workers)',
                                $job->getWaitingTime(),
                                $job->getJobsInQueue(),
                                $job->getWorkers()
                            )
                        );
                        break;

                    case CloudJob::STATUS_PROCESSING:
                        $status->setSummary('Resolving dependencies using Composer Cloud');
                        $status->setDetail('Composer Cloud is sponsored by the Contao Association');
                        break;

                    case CloudJob::STATUS_ERROR:
                        $status->setSummary('Failed resolving dependencies …');
                        $status->setConsole($cloud->getOutput($job));
                        $status->setStatus(TaskStatus::STATUS_ERROR);
                        break;

                    case CloudJob::STATUS_FINISHED:
                        $this->filesystem->dumpFile(
                            $this->rootFiles['lock'],
                            $cloud->getComposerLock($job)
                        );
                        $this->filesystem->dumpFile(
                            $this->rootFiles['json'],
                            $cloud->getComposerJson($job)
                        );
                        $config->setState('locked', true);
                        break;

                    default:
                        throw new \RuntimeException(sprintf('Unknown cloud status "%s"', $job->getStatus()));
                }
            }
        } elseif (!$process->isTerminated()) {
            $status->setSummary('Installing Composer dependencies …');
            $status->setDetail($process->getCommandLine());

            if (!$process->isStarted()) {
                $process->start();
            }

        } elseif (!$process->isSuccessful()) {

            $status->setSummary('Failed to install Composer dependencies');
            $status->setDetail($process->getCommandLine());
            $status->setStatus(TaskStatus::STATUS_ERROR);

            if ($this->config->get('disable_cloud', false)) {
                $this->getComposerDefinition($config)->restoreBackup();
            }
        } else {
            $status->setSummary('Contao installed successfully');
            $status->setStatus(TaskStatus::STATUS_COMPLETE);
        }

        return $status;
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function abort(TaskConfig $config)
    {
        $config->setState('stopping', true);

        return $this->update($config);
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function delete(TaskConfig $config)
    {
        $status = $this->abort($config);

        if (!$status->isActive()) {
            $this->getProcess($config)->delete();

            if ($jobId = $config->getState('cloud-job')) {
                (new CloudResolver())->deleteJob($jobId);
            }
        }

        return $status;
    }

    protected function getInstallProcessArguments($dryRun = false)
    {
        $arguments = [
            'composer',
            'install',
            '--prefer-dist',
            '--no-dev',
            '--no-progress',
            '--no-suggest',
            '--no-interaction',
            '--optimize-autoloader',
        ];

        if ($dryRun) {
            $arguments[] = '--dry-run';
        }

        return $arguments;
    }

    protected function getUpdateProcessArguments(array $packages = [], $dryRun = false)
    {
        $arguments = array_merge(
            [
                'composer',
                'update',
            ],
            $packages,
            [
                '--with-dependencies',
                '--prefer-dist',
                '--no-dev',
                '--no-progress',
                '--no-suggest',
                '--no-interaction',
                '--optimize-autoloader',
            ]
        );

        if ($dryRun) {
            $arguments[] = '--dry-run';
        }

        return $arguments;
    }

    /**
     * @return ProcessController
     */
    abstract protected function getProcess(TaskConfig $config);

    /**
     * @param TaskConfig $config
     *
     * @return CloudChanges
     */
    abstract protected function getComposerDefinition(TaskConfig $config);

}
