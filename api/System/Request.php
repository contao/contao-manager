<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\System;

use Composer\CaBundle\CaBundle;
use Composer\Util\RemoteFilesystem;
use Composer\Util\StreamContextFactory;
use Contao\ManagerApi\ApiKernel;
use Psr\Log\LoggerInterface;

class Request
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ApiKernel $kernel, LoggerInterface $logger = null)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    /**
     * @throws \ErrorException
     */
    public function get($url, &$statusCode = null, $catch = false)
    {
        $context = $this->createStreamContext($url);

        try {
            $content = file_get_contents($url, false, $context);
            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\ErrorException $e) {
            if ($catch) {
                return false;
            }

            throw $e;
        }

        return $content;
    }

    /**
     * @throws \ErrorException
     */
    public function getStream($url, &$statusCode = null, $catch = false)
    {
        $context = $this->createStreamContext($url);

        try {
            $stream = fopen($url, 'rb', false, $context);
            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\ErrorException $e) {
            if ($catch) {
                return false;
            }

            throw $e;
        }

        return $stream;
    }

    /**
     * @throws \ErrorException
     */
    public function getJson($url, array $headers = [], &$statusCode = null, $catch = false)
    {
        $headers[] = 'Accept: application/json';

        $context = $this->createStreamContext($url, ['http' => ['header' => $headers]]);

        try {
            $content = file_get_contents($url, false, $context);
            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\ErrorException $e) {
            if ($catch) {
                return false;
            }

            throw $e;
        }

        return $content;
    }

    /**
     * @throws \ErrorException
     */
    public function postJson($url, $content, array $headers = [], &$statusCode = null, $catch = false)
    {
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $options = ['http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => $content,
        ]];

        $context = $this->createStreamContext($url, $options);

        try {
            $content = file_get_contents($url, false, $context);
            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\ErrorException $e) {
            if ($catch) {
                return false;
            }

            throw $e;
        }

        return $content;
    }

    /**
     * @throws \ErrorException
     */
    public function deleteJson($url, array $headers = [], &$statusCode = null, $catch = false)
    {
        $headers[] = 'Accept: application/json';
        $options = ['http' => [
            'method' => 'DELETE',
            'header' => $headers,
        ]];

        $context = $this->createStreamContext($url, $options);

        try {
            $content = file_get_contents($url, false, $context);
            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\ErrorException $e) {
            if ($catch) {
                return false;
            }

            throw $e;
        }

        return $content;
    }

    private function createStreamContext($url, array $options = [])
    {
        $tlsDefaults = $this->getTlsDefaults($options);
        $options = array_replace_recursive($tlsDefaults, $options);

        if (isset($options['http']['header']) && !\is_array($options['http']['header'])) {
            $options['http']['header'] = [$options['http']['header']];
        }

        $options['http']['header'][] = sprintf(
            'User-Agent: Contao Manager/%s (%s; %s; %s%s)',
            $this->kernel->getVersion() === '@'.'package_version'.'@' ? 'source' : $this->kernel->getVersion(),
            function_exists('php_uname') ? php_uname('s') : 'Unknown',
            function_exists('php_uname') ? php_uname('r') : 'Unknown',
            PHP_VERSION,
            getenv('CI') ? '; CI' : ''
        );

        return StreamContextFactory::getContext($url, $options);
    }

    /**
     * @see RemoteFilesystem::getTlsDefaults()
     */
    private function getTlsDefaults(array $options)
    {
        $ciphers = implode(':', array(
            'ECDHE-RSA-AES128-GCM-SHA256',
            'ECDHE-ECDSA-AES128-GCM-SHA256',
            'ECDHE-RSA-AES256-GCM-SHA384',
            'ECDHE-ECDSA-AES256-GCM-SHA384',
            'DHE-RSA-AES128-GCM-SHA256',
            'DHE-DSS-AES128-GCM-SHA256',
            'kEDH+AESGCM',
            'ECDHE-RSA-AES128-SHA256',
            'ECDHE-ECDSA-AES128-SHA256',
            'ECDHE-RSA-AES128-SHA',
            'ECDHE-ECDSA-AES128-SHA',
            'ECDHE-RSA-AES256-SHA384',
            'ECDHE-ECDSA-AES256-SHA384',
            'ECDHE-RSA-AES256-SHA',
            'ECDHE-ECDSA-AES256-SHA',
            'DHE-RSA-AES128-SHA256',
            'DHE-RSA-AES128-SHA',
            'DHE-DSS-AES128-SHA256',
            'DHE-RSA-AES256-SHA256',
            'DHE-DSS-AES256-SHA',
            'DHE-RSA-AES256-SHA',
            'AES128-GCM-SHA256',
            'AES256-GCM-SHA384',
            'AES128-SHA256',
            'AES256-SHA256',
            'AES128-SHA',
            'AES256-SHA',
            'AES',
            'CAMELLIA',
            'DES-CBC3-SHA',
            '!aNULL',
            '!eNULL',
            '!EXPORT',
            '!DES',
            '!RC4',
            '!MD5',
            '!PSK',
            '!aECDH',
            '!EDH-DSS-DES-CBC3-SHA',
            '!EDH-RSA-DES-CBC3-SHA',
            '!KRB5-DES-CBC3-SHA',
        ));

        /**
         * CN_match and SNI_server_name are only known once a URL is passed.
         * They will be set in the getOptionsForUrl() method which receives a URL.
         *
         * cafile or capath can be overridden by passing in those options to constructor.
         */
        $defaults = array(
            'ssl' => array(
                'ciphers' => $ciphers,
                'verify_peer' => true,
                'verify_depth' => 7,
                'SNI_enabled' => true,
            ),
        );

        if (isset($options['ssl'])) {
            $defaults['ssl'] = array_replace_recursive($defaults['ssl'], $options['ssl']);
        }

        /**
         * Attempt to find a local cafile or throw an exception if none pre-set
         * The user may go download one if this occurs.
         */
        if (!isset($defaults['ssl']['cafile']) && !isset($defaults['ssl']['capath'])) {
            $result = CaBundle::getSystemCaRootBundlePath($this->logger);

            if (is_dir($result)) {
                $defaults['ssl']['capath'] = $result;
            } else {
                $defaults['ssl']['cafile'] = $result;
            }
        }

        /**
         * Disable TLS compression to prevent CRIME attacks where supported.
         */
        if (PHP_VERSION_ID >= 50413) {
            $defaults['ssl']['disable_compression'] = true;
        }

        return $defaults;
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
