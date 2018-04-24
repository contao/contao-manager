<?php

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
            'platform' => $definition->getPlatform()
        ];

        // TODO add update set from CloudChanges
        $options = [RequestOptions::JSON => $data];

        $response = $this->http->post(self::API_URL . '/jobs', $options);

        return new CloudJob(JsonFile::parseJson((string) $response->getBody()));
    }

    /**
     * @param $jobId
     *
     * @return CloudJob|null
     */
    public function getJob($jobId)
    {
        $response = $this->http->get(self::API_URL.'/jobs/'.$jobId);

        return new CloudJob(json_decode((string) $response->getBody(), true));
    }

    public function deleteJob($jobId)
    {
        $response = $this->http->delete(self::API_URL.'/jobs/'.$jobId);
    }

    public function getComposerJson(CloudJob $job)
    {
        $response = $this->http->get(self::API_URL.$job->getLink(CloudJob::LINK_JSON));

        return (string) $response->getBody();
    }

    public function getComposerLock(CloudJob $job)
    {
        if (!$job->isSuccessful()) {
            return null;
        }

        $response = $this->http->get(self::API_URL.$job->getLink(CloudJob::LINK_LOCK));

        return (string) $response->getBody();
    }

    public function getOutput(CloudJob $job)
    {
        if ($job->isQueued()) {
            return null;
        }

        $response = $this->http->get(self::API_URL.$job->getLink(CloudJob::LINK_OUTPUT));

        return (string) $response->getBody();
    }
}
