<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudException;
use Contao\ManagerApi\Composer\CloudJob;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\I18n\Translator;
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
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CloudJob
     */
    private $job;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * Constructor.
     *
     * @param CloudResolver $cloud
     * @param CloudChanges  $changes
     * @param TaskConfig    $taskConfig
     * @param Environment   $environment
     * @param Translator    $translator
     * @param Filesystem    $filesystem
     */
    public function __construct(CloudResolver $cloud, CloudChanges $changes, TaskConfig $taskConfig, Environment $environment, Translator $translator, Filesystem $filesystem)
    {
        $this->cloud = $cloud;
        $this->changes = $changes;
        $this->taskConfig = $taskConfig;
        $this->environment = $environment;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
    }

    public function isStarted()
    {
        try {
            return (bool) $this->taskConfig->getState('cloud-job-queued');
        } catch (\Exception $e) {
            $this->exception = $e;

            return true;
        }
    }

    public function isRunning()
    {
        try {
            $job = $this->getCurrentJob();

            return $job instanceof CloudJob && null === $this->taskConfig->getState('cloud-job-successful');
        } catch (\Exception $e) {
            $this->exception = $e;

            return false;
        }
    }

    public function isSuccessful()
    {
        return (bool) $this->taskConfig->getState('cloud-job-successful', false);
    }

    public function hasError()
    {
        return false === $this->taskConfig->getState('cloud-job-successful');
    }

    public function run()
    {
        try {
            $job = $this->getCurrentJob();

            if (!$job instanceof CloudJob) {
                $this->taskConfig->setState('cloud-job-queued', time());
                $this->job = $job = $this->cloud->createJob($this->changes, $this->environment->isDebug());
                $this->taskConfig->setState('cloud-job', $this->job->getId());
            }

            if ($job->isSuccessful() && !$this->taskConfig->getState('cloud-job-successful', false)) {
                $this->filesystem->dumpFile(
                    $this->environment->getLockFile(),
                    $this->cloud->getComposerLock($job)
                );

                $this->taskConfig->setState('cloud-job-successful', true);
            }

            if ($job->isFailed()) {
                $this->taskConfig->setState('cloud-job-successful', false);
            }
        } catch (\Exception $e) {
            $this->exception = $e;
            $this->taskConfig->setState('cloud-job-successful', false);
        }
    }

    public function abort()
    {
        $this->taskConfig->setState('cloud-job-successful', false);
    }

    public function delete()
    {
        try {
            $this->cloud->deleteJob($this->taskConfig->getState('cloud-job'));
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    public function updateStatus(TaskStatus $status)
    {
        if ($this->exception instanceof CloudException) {
            $status->addConsole(
                sprintf(
                    "> The Composer Cloud failed with status code %s\n\n  %s",
                    $this->exception->getStatusCode(),
                    $this->exception->getErrorMessage()
                )
            );

            return;
        }

        if ($this->exception instanceof \Exception) {
            $status->addConsole($this->exception->getMessage());

            return;
        }

        try {
            $job = $this->getCurrentJob();
        } catch (\Exception $e) {
            $this->exception = $e;
            $status->addConsole($this->exception->getMessage());

            return;
        }

        if (!$job instanceof CloudJob) {
            return;
        }

        $console = '> Resolving dependencies using Composer Cloud '.$job->getVersion();
//        $console .= "\n!!! Current server is sponsored by: ".$job->getSponsor()." !!!\n";

        switch ($job->getStatus()) {
            case CloudJob::STATUS_QUEUED:
                $status->setSummary(
                    $this->translator->trans(
                        'taskoperation.cloud.queuedSummary',
                        ['seconds' => time() - $this->taskConfig->getState('cloud-job-queued')]
                    )
                );
                $status->setDetail(
                    $this->translator->trans(
                        'taskoperation.cloud.queuedDetail',
                        [
                            'seconds' => $job->getWaitingTime(),
                            'jobs' => $job->getJobsInQueue(),
                            'workers' => $job->getWorkers(),
                        ]
                    )
                );
                break;

            case CloudJob::STATUS_PROCESSING:
                $profile = $this->getCurrentProfile($this->cloud->getOutput($job));
                $seconds = time() - $this->taskConfig->getState('cloud-job-processing');

                $status->setSummary($this->translator->trans('taskoperation.cloud.processingSummary'));

                $status->setDetail(
                    $this->translator->trans(
                           'taskoperation.cloud.processingDetail',
                           ['job' => '['.substr($job->getId(), 0, 8).'…]', 'seconds' => $seconds]
                    ).' '.$profile
                );

                $status->addConsole(
                    $console."\n\n ".$this->translator->trans(
                        'taskoperation.cloud.processingDetail',
                        ['job' => $job->getId(), 'seconds' => $seconds]
                    ).' '.$profile
                );

                if ($this->environment->isDebug()) {
                    $status->addConsole($this->cloud->getOutput($job));
                }
                break;

            case CloudJob::STATUS_ERROR:
                $output = sprintf("%s\n\n# Cloud Job ID %s failed", $this->cloud->getOutput($job), $job->getId());
                $status->setSummary($this->translator->trans('taskoperation.cloud.errorSummary'));
                $status->addConsole($output, $console);
                $status->setStatus(TaskStatus::STATUS_ERROR);
                break;

            case CloudJob::STATUS_FINISHED:
                $seconds = $this->taskConfig->getState('cloud-job-finished') - $this->taskConfig->getState('cloud-job-processing');

                $profile = $this->getFinalProfile($this->cloud->getOutput($job));
                preg_match('{Memory usage: ([^ ]+) \(peak: ([^\)]+)\), time: ([0-9\.]+s)\.}', $profile, $match);

                $detail = $this->translator->trans(
                    'taskoperation.cloud.finishedDetail',
                    [
                        'job' => $job->getId(),
                        'memory' => $match[1],
                        'peak' => $match[2],
                        'time' => $match[3],
                    ]
                );

                $status->setSummary($this->translator->trans('taskoperation.cloud.finishedSummary', ['seconds' => $seconds]));
                $status->setDetail($detail);
                $status->addConsole("# Job ID {$job->getId()} completed in $seconds seconds\n# ".$profile, $console);
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

        if ($this->job instanceof CloudJob
            && $this->job->isProcessing()
            && !$this->taskConfig->getState('cloud-job-processing')
        ) {
            $this->taskConfig->setState('cloud-job-processing', time());
        }

        if ($this->job instanceof CloudJob
            && ($this->job->isSuccessful() || $this->job->isFailed())
            && !$this->taskConfig->getState('cloud-job-finished')
        ) {
            $this->taskConfig->setState('cloud-job-finished', time());
        }

        return $this->job;
    }

    private function getCurrentProfile($output)
    {
        // [351.1MiB/0.21s]

        $lines = array_reverse(explode("\n", $output));

        foreach ($lines as $line) {
            if (preg_match('{^\[([\d\.]+MiB)/[\d\.]+s\]}', $line, $match)) {
                return $match[0];
            }
        }

        return '';
    }

    private function getFinalProfile($output)
    {
        // Memory usage: 353.94MB (peak: 1327.09MB), time: 160.17s

        $lines = array_reverse(explode("\n", $output));

        foreach ($lines as $line) {
            if (false !== ($pos = strpos($line, 'Memory usage:'))) {
                return substr($line, $pos);
            }
        }

        return '';
    }
}
