<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Composer;

use Composer\Json\JsonFile;
use Contao\ManagerApi\System\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * @ServiceTag("monolog.logger", channel="tasks")
 */
class CloudResolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const API_URL = 'https://composer-resolver.cloud';

    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Creates a Cloud job for given composer changes.
     */
    public function createJob(CloudChanges $changes, Environment $environment, string $client = 'contao', string $authorization = null): CloudJob
    {
        $environment->reset();

        $data = [
            'composerJson' => $environment->getComposerJson(),
            'composerLock' => $environment->getComposerLock(),
            'platform' => $environment->getPlatformPackages(),
            'localPackages' => $environment->getLocalPackages(),
        ];

        $command = $changes->getUpdates();
        $command[] = '--with-dependencies';
        $command[] = '--profile';

        if ($environment->isDebug()) {
            $command[] = '-vvv';
        }

        $body = json_encode($data);
        $headers = [
            'Composer-Resolver-Client: '.$client,
            'Composer-Resolver-Command: '.implode(' ', $command),
        ];

        if (null !== $authorization) {
            $headers[] = 'Authorization: '.$authorization;
        }

        if (null !== $this->logger) {
            $this->logger->info('Creating Composer Cloud job', [
                'headers' => $headers,
                'body' => $body,
            ]);
        }

        $content = $this->request->postJson(self::API_URL.'/jobs', $body, $headers, $statusCode);

        switch ($statusCode) {
            case 200:
            case 201:
            case 202: // Location redirect to fetch the job content
                return new CloudJob(JsonFile::parseJson($content));

            case 400:
                throw new CloudException('Composer Resolver did not accept the API call', $statusCode, $content, $body);
            case 503:
                throw new CloudException('Too many jobs on the Composer Resolver queue.', $statusCode, $content, $body);
            default:
                throw $this->createUnknownResponseException($statusCode, $content, $body);
        }
    }

    /**
     * Gets job information from the Composer Cloud.
     */
    public function getJob(string $jobId): ?CloudJob
    {
        if (!$jobId) {
            return null;
        }

        $content = $this->request->getJson(
            self::API_URL.'/jobs/'.$jobId,
            ['Composer-Resolver-Client: contao'],
            $statusCode
        );

        switch ($statusCode) {
            case 200:
            case 202:
                return new CloudJob(JsonFile::parseJson($content, true));

            default:
                throw $this->createUnknownResponseException($statusCode, $content);
        }
    }

    /**
     * Deletes a cloud job and returns whether it was successful.
     */
    public function deleteJob(string $jobId): bool
    {
        if (!$jobId) {
            return false;
        }

        $content = $this->request->deleteJson(
            self::API_URL.'/jobs/'.$jobId,
            ['Composer-Resolver-Client: contao'],
            $statusCode
        );

        if (204 === $statusCode) {
            return true;
        }

        throw $this->createUnknownResponseException($statusCode, $content);
    }

    /**
     * Gets the composer.json file.
     */
    public function getComposerJson(CloudJob $job): string
    {
        return $this->getContent($job->getLink(CloudJob::LINK_JSON));
    }

    /**
     * Gets the composer.lock file or null if the cloud job was not successful.
     */
    public function getComposerLock(CloudJob $job): ?string
    {
        if (!$job->isSuccessful()) {
            return null;
        }

        return $this->getContent($job->getLink(CloudJob::LINK_LOCK));
    }

    /**
     * Gets the console output for a cloud job.
     */
    public function getOutput(CloudJob $job): ?string
    {
        if ($job->isQueued()) {
            return null;
        }

        return $this->getContent($job->getLink(CloudJob::LINK_OUTPUT));
    }

    private function getContent($link): string
    {
        $content = $this->request->getJson(
            self::API_URL.$link,
            ['Composer-Resolver-Client: contao'],
            $statusCode
        );

        switch ($statusCode) {
            case 200:
                return $content;

            default:
                throw $this->createUnknownResponseException($statusCode, $content);
        }
    }

    private function createUnknownResponseException($statusCode, $responseBody, $requestBody = null)
    {
        return new CloudException('Composer Resolver returned an unexpected status code', $statusCode, $responseBody, $requestBody);
    }
}
