<?php

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudJob;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
use Symfony\Component\Filesystem\Filesystem;

class CloudOperation implements TaskOperationInterface
{
    /**
     * @var CloudResolver
     */
    private $cloud;

    /**
     * @var CloudChanges
     */
    private $changes;

    /**
     * @var TaskConfig
     */
    private $taskConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CloudJob
     */
    private $job;

    /**
     * Constructor.
     *
     * @param CloudResolver $cloud
     * @param CloudChanges  $changes
     * @param TaskConfig    $taskConfig
     * @param Environment   $environment
     * @param Filesystem    $filesystem
     */
    public function __construct(CloudResolver $cloud, CloudChanges $changes, TaskConfig $taskConfig, Environment $environment, Filesystem $filesystem)
    {
        $this->cloud = $cloud;
        $this->changes = $changes;
        $this->taskConfig = $taskConfig;
        $this->environment = $environment;
        $this->filesystem = $filesystem;
    }

    public function isStarted()
    {
        return $this->getCurrentJob() instanceof CloudJob;
    }

    public function isRunning()
    {
        $job = $this->getCurrentJob();

        return $job instanceof CloudJob
            && ($job->isQueued()
                || $job->isProcessing()
                || ($job->isSuccessful() && !$this->taskConfig->getState('cloud-job-completed', false))
            );
    }

    public function isSuccessful()
    {
        return (bool) $this->taskConfig->getState('cloud-job-completed', false);
    }

    public function run()
    {
        $job = $this->getCurrentJob();

        if (!$job instanceof CloudJob) {
            $this->job = $this->cloud->createJob($this->changes);
            $this->taskConfig->setState('cloud-job', $this->job->getId());
        }

        if ($job->isSuccessful() && !$this->taskConfig->getState('cloud-job-completed', false)) {
            $this->filesystem->dumpFile(
                $this->environment->getLockFile(),
                $this->cloud->getComposerLock($job)
            );
            $this->filesystem->dumpFile(
                $this->environment->getJsonFile(),
                $this->cloud->getComposerJson($job)
            );

            $this->taskConfig->setState('cloud-job-completed', true);
        }
    }

    public function abort()
    {
        $this->taskConfig->clearState('cloud-job');
        $this->taskConfig->clearState('cloud-job-completed');
    }

    public function delete()
    {
        $this->cloud->deleteJob($this->taskConfig->getState('cloud-job'));
    }

    public function updateStatus(TaskStatus $status)
    {
        $job = $this->getCurrentJob();

        if (!$job instanceof CloudJob) {
            return;
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
                $status->setConsole($this->cloud->getOutput($job));
                $status->setStatus(TaskStatus::STATUS_ERROR);
                break;

            case CloudJob::STATUS_FINISHED:
                break;

            default:
                throw new \RuntimeException(sprintf('Unknown cloud status "%s"', $job->getStatus()));
        }
    }

    /**
     * @return CloudJob|null
     */
    private function getCurrentJob()
    {
        if (null === $this->job) {
            $this->job = $this->cloud->getJob($this->taskConfig->getState('cloud-job'));
        }

        return $this->job;
    }
}
