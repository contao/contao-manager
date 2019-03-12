<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\System;

use Composer\Util\StreamContextFactory;
use Contao\ManagerApi\ApiKernel;

class Request
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    public function __construct(ApiKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function get($url, &$statusCode = null)
    {
        $context = $this->createStreamContext($url);

        $content = @file_get_contents($url, false, $context);
        $statusCode = $this->getLastStatusCode($http_response_header);

        return $content;
    }

    public function getStream($url, &$statusCode = null)
    {
        $context = $this->createStreamContext($url);

        $stream = @fopen($url, 'rb', false, $context);
        $statusCode = $this->getLastStatusCode($http_response_header);

        return $stream;
    }

    public function getJson($url, array $headers = [], &$statusCode = null)
    {
        $headers[] = 'Accept: application/json';

        $context = $this->createStreamContext($url, ['http' => ['header' => $headers]]);

        $content = @file_get_contents($url, false, $context);
        $statusCode = $this->getLastStatusCode($http_response_header);

        return $content;
    }

    public function postJson($url, $content, array $headers = [], &$statusCode = null)
    {
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $options = ['http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => $content,
        ]];

        $context = $this->createStreamContext($url, $options);

        $content = @file_get_contents($url, false, $context);
        $statusCode = $this->getLastStatusCode($http_response_header);

        return $content;
    }

    public function deleteJson($url, array $headers = [], &$statusCode = null)
    {
        $headers[] = 'Accept: application/json';
        $options = ['http' => [
            'method' => 'DELETE',
            'header' => $headers,
        ]];

        $context = $this->createStreamContext($url, $options);

        $content = @file_get_contents($url, false, $context);
        $statusCode = $this->getLastStatusCode($http_response_header);

        return $content;
    }

    private function createStreamContext($url, array $options = [])
    {
        unset($http_response_header);

        if (isset($options['http']['header']) && !\is_array($options['http']['header'])) {
            $options['http']['header'] = [$options['http']['header']];
        }

        $options['http']['header'][] = sprintf(
            'User-Agent: Contao Manager/%s (%s; %s; %s%s)',
            $this->kernel->getVersion() === '@package_version@' ? 'source' : $this->kernel->getVersion(),
            function_exists('php_uname') ? php_uname('s') : 'Unknown',
            function_exists('php_uname') ? php_uname('r') : 'Unknown',
            PHP_VERSION,
            getenv('CI') ? '; CI' : ''
        );

        return StreamContextFactory::getContext($url, $options);
    }

    private function getLastStatusCode($http_response_header)
    {
        if (!\is_array($http_response_header)) {
            return 500;
        }

        // Reverse the headers so we find the last HTTP status code if the request was redirected
        // See http://php.net/manual/en/reserved.variables.httpresponseheader.php#122362
        $http_response_header = array_reverse($http_response_header);

        foreach ($http_response_header as $header) {
            if (preg_match('{^HTTP/.+ ([0-9]{3}) }i', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 500;
    }
}
