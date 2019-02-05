<?php

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

class CloudResolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const API_URL = 'https://composer-resolver.cloud';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $output = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Creates a Cloud job for given composer changes.
     *
     * @param CloudChanges $definition
     * @param bool         $debugMode
     *
     * @return CloudJob
     */
    public function createJob(CloudChanges $definition, $debugMode = false)
    {
        $data = [
            'composerJson' => $definition->getJson(),
            'composerLock' => $definition->getLock(),
            'platform' => $definition->getPlatform(),
            'localPackages' => $definition->getLocalPackages(),
        ];

        $command = $definition->getUpdates();
        $command[] = '--with-dependencies';
        $command[] = '--profile';

        if ($debugMode) {
            $command[] = '-vvv';
        }

        $body = json_encode($data);
        $headers = [
            'Composer-Resolver-Client: contao',
            'Composer-Resolver-Command: '.implode(' ', $command),
        ];

        if (null !== $this->logger) {
            $this->logger->info('Creating Composer Cloud job', [
                'headers' => $headers,
                'body' => $body,
            ]);
        }

        $content = $this->request->postJson(self::API_URL.'/jobs', $body, $headers, $statusCode);

        switch ($statusCode) {
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
     *
     * @param string $jobId
     *
     * @return CloudJob|null
     */
    public function getJob($jobId)
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
     *
     * @param string $jobId
     *
     * @return bool
     */
    public function deleteJob($jobId)
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
     *
     * @param CloudJob $job
     *
     * @return string
     */
    public function getComposerJson(CloudJob $job)
    {
        return $this->getContent($job->getLink(CloudJob::LINK_JSON));
    }

    /**
     * Gets the composer.lock file or null if the cloud job was not successful.
     *
     * @param CloudJob $job
     *
     * @return null|string
     */
    public function getComposerLock(CloudJob $job)
    {
        if (!$job->isSuccessful()) {
            return null;
        }

        return $this->getContent($job->getLink(CloudJob::LINK_LOCK));
    }

    /**
     * Gets the console output for a cloud job.
     *
     * @param CloudJob $job
     *
     * @return null|string
     */
    public function getOutput(CloudJob $job)
    {
        if ($job->isQueued()) {
            return null;
        }

        if (!isset($this->output[$job->getId()])) {
            $this->output[$job->getId()] = $this->getContent($job->getLink(CloudJob::LINK_OUTPUT));
        }

        return $this->output[$job->getId()];
    }

    private function getContent($link)
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
