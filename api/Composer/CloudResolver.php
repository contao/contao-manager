<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Composer;

use Composer\Json\JsonFile;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class CloudResolver
{
    const API_URL = 'https://resolve.contao.org';

    /**
     * @var Client
     */
    private $http;

    public function __construct()
    {
        $this->http = new Client();
    }

    public function createJob(CloudChanges $definition)
    {
        $data = [
            'composerJson' => $definition->getJson(),
            'composerLock' => $definition->getLock(),
            'platform' => $definition->getPlatform(),
        ];

        // TODO add update set from CloudChanges
        $options = [RequestOptions::JSON => $data];

        $response = $this->http->request('POST', self::API_URL.'/jobs', $options);

        return new CloudJob(JsonFile::parseJson((string) $response->getBody()));
    }

    /**
     * @param $jobId
     *
     * @return CloudJob|null
     */
    public function getJob($jobId)
    {
        if (!$jobId) {
            return null;
        }

        $response = $this->http->request('GET', self::API_URL.'/jobs/'.$jobId);

        return new CloudJob(json_decode((string) $response->getBody(), true));
    }

    public function deleteJob($jobId)
    {
        if (!$jobId) {
            return;
        }

        $response = $this->http->request('DELETE', self::API_URL.'/jobs/'.$jobId);
    }

    public function getComposerJson(CloudJob $job)
    {
        $response = $this->http->request('GET', self::API_URL.$job->getLink(CloudJob::LINK_JSON));

        return (string) $response->getBody();
    }

    public function getComposerLock(CloudJob $job)
    {
        if (!$job->isSuccessful()) {
            return null;
        }

        $response = $this->http->request('GET', self::API_URL.$job->getLink(CloudJob::LINK_LOCK));

        return (string) $response->getBody();
    }

    public function getOutput(CloudJob $job)
    {
        if ($job->isQueued()) {
            return null;
        }

        $response = $this->http->request('GET', self::API_URL.$job->getLink(CloudJob::LINK_OUTPUT));

        return (string) $response->getBody();
    }
}
