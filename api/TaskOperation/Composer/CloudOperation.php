<?php

declare(strict_types=1);

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
use Contao\ManagerApi\Exception\RequestException;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\ConsoleOutput;
use Contao\ManagerApi\TaskOperation\SponsoredOperationInterface;
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
use Symfony\Component\Filesystem\Filesystem;

class CloudOperation implements TaskOperationInterface, SponsoredOperationInterface
{
    private const CLOUD_ERROR = 'Error handling the Composer Resolver Cloud. Please try again later.';

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
     * @var string
     */
    private $output;

    /**
     * Constructor.
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

    public function getSummary(): string
    {
        $summary = 'composer update ';

        if (!empty($this->changes->getUpdates())) {
            $summary .= implode(' ', $this->changes->getUpdates());
        }

        $summary .= ' --no-install';

        if ($this->changes->getDryRun()) {
            $summary .= ' --dry-run';
        }

        return $summary;
    }

    public function getDetails(): ?string
    {
        $job = $this->getCurrentJob();

        if (!$job instanceof CloudJob) {
            return '';
        }

        switch ($job->getStatus()) {
            case CloudJob::STATUS_QUEUED:
                return $this->translator->trans(
                    'taskoperation.cloud.queued',
                    [
                        'seconds' => $job->getWaitingTime(),
                        'jobs' => $job->getJobsInQueue() + $job->getWorkers(),
                        'workers' => $job->getWorkers(),
                    ]
                );

            case CloudJob::STATUS_PROCESSING:
                $seconds = $this->taskConfig->getState('cloud-job-processing');

                if (!$seconds) {
                    return '';
                }

                $seconds = time() - $seconds;

                return $this->translator->trans(
                    'taskoperation.cloud.processing',
                    ['seconds' => $seconds]
                );

            case CloudJob::STATUS_ERROR:
                return '';

            case CloudJob::STATUS_FINISHED:
                $seconds = $this->taskConfig->getState('cloud-job-finished') - $this->taskConfig->getState('cloud-job-processing');
                $profile = $this->getFinalProfile($this->getOutput());
                preg_match('{Memory usage: ([^ ]+) \(peak: ([^)]+)\), time: ([0-9.]+s)\.}', $profile, $match);

                return $this->translator->trans(
                    'taskoperation.cloud.finished',
                    [
                        'job' => $job->getId(),
                        'memory' => $match[1] ?? '',
                        'peak' => $match[2] ?? '',
                        'time' => $match[3] ?? '',
                        'seconds' => $seconds,
                    ]
                );
        }

        return '';
    }

    public function getConsole(): ConsoleOutput
    {
        $console = new ConsoleOutput();
        $job = $this->getCurrentJob();

        if ($this->exception instanceof CloudException) {
            return $console->add(
                sprintf(
                    "> The Composer Resolver Cloud failed with status code %s\n\n  %s",
                    $this->exception->getStatusCode(),
                    $this->exception->getErrorMessage()
                )
            );
        }

        if ($this->exception instanceof RequestException && 404 === $this->exception->getStatusCode()) {
            return $console->add(self::CLOUD_ERROR);
        }

        if ($this->exception instanceof \Exception) {
            return $console->add($this->exception->getMessage());
        }

        if (!$job instanceof CloudJob) {
            if ($this->hasError()) {
                $console->add(self::CLOUD_ERROR);
            }

            return $console;
        }

        $title = '> Resolving dependencies using Composer Cloud '.$job->getVersion();

        switch ($job->getStatus()) {
            case CloudJob::STATUS_QUEUED:
                break;

            case CloudJob::STATUS_PROCESSING:
                if ($this->environment->isDebug()) {
                    $console->add($this->getOutput(), $title);
                } else {
                    $console->add($title);
                }
                break;

            case CloudJob::STATUS_ERROR:
                $console->add(
                    sprintf("%s\n\n# Cloud Job ID %s failed", $this->getOutput(), $job->getId()),
                    $title
                );
                break;

            case CloudJob::STATUS_FINISHED:
                $output = $this->getOutput();
                $seconds = $this->taskConfig->getState('cloud-job-finished') - $this->taskConfig->getState('cloud-job-processing');

                $profile = $this->getFinalProfile($output);
                preg_match('{Memory usage: ([^ ]+) \(peak: ([^)]+)\), time: ([0-9.]+s)\.}', $profile, $match);

                $console->add($output, $title);
                $console->add("# Job ID {$job->getId()} completed in $seconds seconds\n# ".$profile);
                break;

            default:
                throw new \RuntimeException(sprintf('Unknown cloud status "%s"', $job->getStatus()));
        }

        return $console;
    }

    public function isStarted(): bool
    {
        try {
            return null !== $this->taskConfig->getState('cloud-job');
        } catch (\Exception $e) {
            $this->exception = $e;

            return true;
        }
    }

    public function isRunning(): bool
    {
        try {
            return $this->isStarted() && null === $this->taskConfig->getState('cloud-job-successful');
        } catch (\Exception $e) {
            $this->exception = $e;

            return false;
        }
    }

    public function isSuccessful(): bool
    {
        return (bool) $this->taskConfig->getState('cloud-job-successful', false);
    }

    public function hasError(): bool
    {
        return false === $this->taskConfig->getState('cloud-job-successful');
    }

    public function run(): void
    {
        try {
            if (null === $this->taskConfig->getState('cloud-job')) {
                // Retry to create Cloud job, the first request always fails on XAMPP for unknown reason
                $attempts = $this->taskConfig->getState('cloud-job-attempts', 0);

                if ($attempts >= 5) {
                    $this->taskConfig->setState('cloud-job-successful', false);
                    $this->output = self::CLOUD_ERROR;

                    return;
                }

                $this->taskConfig->setState('cloud-job-attempts', $attempts + 1);

                $this->job = $this->cloud->createJob($this->changes, $this->environment);
                $this->taskConfig->setState('cloud-job', $this->job->getId());

                return;
            }

            $job = $this->getCurrentJob();

            if (!$job instanceof CloudJob) {
                return;
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
            $this->output = self::CLOUD_ERROR;
        }
    }

    public function abort(): void
    {
        $this->taskConfig->setState('cloud-job-successful', false);
    }

    public function delete(): void
    {
        try {
            $this->output = $this->taskConfig->getState('cloud-job-output');
            $this->cloud->deleteJob((string) $this->taskConfig->getState('cloud-job'));
        } catch (\Exception $e) {
            $this->exception = $e;
        }
    }

    public function getSponsor(): ?array
    {
        if (!$this->job instanceof CloudJob) {
            return null;
        }

        return $this->job->getSponsor();
    }

    private function getCurrentJob(): ?CloudJob
    {
        if ($this->job instanceof CloudJob) {
            return $this->job;
        }

        if (null === $this->taskConfig->getState('cloud-job')) {
            return null;
        }

        try {
            if (\is_array($content = $this->taskConfig->getState('cloud-job-status'))) {
                $this->job = new CloudJob($content);

                if (null !== $this->taskConfig->getState('cloud-job-successful')) {
                    $this->output = $this->taskConfig->getState('cloud-job-output');

                    return $this->job;
                }

                $lastUpdated = time() - $this->taskConfig->getState('cloud-job-updated', time());
                $isProcessing = $this->taskConfig->getState('cloud-job-processing', 0) > 0;

                if (($isProcessing && $lastUpdated <= 5) || $lastUpdated <= 10) {
                    $this->output = $this->taskConfig->getState('cloud-job-output');

                    return $this->job;
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        try {
            $this->job = $this->cloud->getJob((string) $this->taskConfig->getState('cloud-job'));
        } catch (\Exception $e) {
            $this->exception = $e;

            if ($e instanceof CloudException && $e->isClientError()) {
                $this->taskConfig->setState('cloud-job-successful', false);
            }

            return $this->job;
        }

        if (!$this->job instanceof CloudJob) {
            return null;
        }

        $this->taskConfig->setState('cloud-job-status', $this->job->jsonSerialize());
        $this->taskConfig->setState('cloud-job-updated', time());

        if (
            $this->job->isProcessing()
            && !$this->taskConfig->getState('cloud-job-processing')
        ) {
            $this->taskConfig->setState('cloud-job-processing', time());
        }

        if (
            ($this->job->isSuccessful() || $this->job->isFailed())
            && !$this->taskConfig->getState('cloud-job-finished')
        ) {
            $this->taskConfig->setState('cloud-job-finished', time());
        }

        return $this->job;
    }

    private function getFinalProfile(string $output): string
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

    private function getOutput(): string
    {
        if (null !== $this->output) {
            return $this->output;
        }

        $job = $this->getCurrentJob();

        if (null === $job) {
            return $this->output = self::CLOUD_ERROR;
        }

        try {
            $this->output = $this->cloud->getOutput($job);

            if (null === $this->output) {
                $this->output = self::CLOUD_ERROR;
            } else {
                $this->taskConfig->setState('cloud-job-output', $this->output);
            }

            return $this->output;
        } catch (\Exception $exception) {
            return $this->output = self::CLOUD_ERROR;
        }
    }
}
