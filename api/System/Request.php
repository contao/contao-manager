<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\System;

use Composer\CaBundle\CaBundle;
use Composer\Util\Platform;
use Composer\Util\StreamContextFactory;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Exception\RequestException;
use Psr\Log\LoggerInterface;

class Request
{
    private const DEFAULT_TIMEOUT = 5;

    public function __construct(
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function get(string $url, ?int &$statusCode = null, bool $catch = false, int $timeout = self::DEFAULT_TIMEOUT): string|null
    {
        return $this->getContent($url, $statusCode, [], $catch, $timeout);
    }

    /**
     * @return resource|false
     */
    public function getStream(string $url, ?int &$statusCode = null, bool $catch = false)
    {
        $context = $this->createStreamContext($url, 0);

        try {
            $stream = fopen($url, 'r', false, $context);
            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\Throwable $throwable) {
            if ($catch) {
                return false;
            }

            throw new RequestException($url, $this->getLastStatusCode($http_response_header ?? null), $throwable);
        }

        return $stream;
    }

    public function getJson(string $url, array $headers = [], ?int &$statusCode = null, bool $catch = false): string|null
    {
        $headers[] = 'Accept: application/json';

        return $this->getContent($url, $statusCode, ['http' => ['header' => $headers]], $catch);
    }

    public function postJson(string $url, string $content, array $headers = [], ?int &$statusCode = null, bool $catch = false): string|null
    {
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $options = ['http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => $content,
        ]];

        return $this->getContent($url, $statusCode, $options, $catch);
    }

    public function deleteJson(string $url, array $headers = [], ?int &$statusCode = null, bool $catch = false): string|null
    {
        $headers[] = 'Accept: application/json';
        $options = ['http' => [
            'method' => 'DELETE',
            'header' => $headers,
        ]];

        return $this->getContent($url, $statusCode, $options, $catch);
    }

    private function getContent(string $url, ?int &$statusCode, array $options, bool $catch, int $timeout = self::DEFAULT_TIMEOUT): string|null
    {
        $context = $this->createStreamContext($url, $timeout, $options);

        try {
            if (false === ($content = file_get_contents($url, false, $context))) {
                throw new \RuntimeException();
            }

            $statusCode = $this->getLastStatusCode($http_response_header);
        } catch (\Throwable $throwable) {
            if ($catch) {
                return null;
            }

            throw new RequestException($url, $this->getLastStatusCode($http_response_header ?? null), $throwable);
        }

        return $content;
    }

    /**
     * @return resource
     */
    private function createStreamContext(string $url, int $timeout = self::DEFAULT_TIMEOUT, array $options = [])
    {
        $tlsDefaults = $this->getTlsDefaults($options);
        $options = array_replace_recursive($tlsDefaults, $options);

        if ($timeout > 0) {
            $options['http']['timeout'] ??= $timeout;
        }

        $options['http']['ignore_errors'] ??= true;

        if (isset($options['http']['header']) && !\is_array($options['http']['header'])) {
            $options['http']['header'] = [$options['http']['header']];
        }

        $options['http']['header'][] = \sprintf(
            'User-Agent: Contao Manager/%s (%s; %s; %s%s)',
            ApiKernel::VERSION_KEY === ApiKernel::MANAGER_VERSION ? 'source' : ApiKernel::MANAGER_VERSION,
            \function_exists('php_uname') ? php_uname('s') : 'Unknown',
            \function_exists('php_uname') ? php_uname('r') : 'Unknown',
            'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
            Platform::getEnv('CI') ? '; CI' : '',
        );

        return StreamContextFactory::getContext($url, $options);
    }

    /**
     * @see \Composer\Util\RemoteFilesystem::getTlsDefaults()
     */
    private function getTlsDefaults(array $options): array
    {
        $ciphers = implode(':', [
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
        ]);

        /**
         * CN_match and SNI_server_name are only known once a URL is passed. They will be
         * set in the getOptionsForUrl() method which receives a URL.
         *
         * cafile or capath can be overridden by passing in those options to constructor.
         */
        $defaults = [
            'ssl' => [
                'ciphers' => $ciphers,
                'verify_peer' => true,
                'verify_depth' => 7,
                'SNI_enabled' => true,
            ],
        ];

        if (isset($options['ssl'])) {
            $defaults['ssl'] = array_replace_recursive($defaults['ssl'], $options['ssl']);
        }

        /*
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

        /*
         * Disable TLS compression to prevent CRIME attacks where supported.
         */
        $defaults['ssl']['disable_compression'] = true;

        return $defaults;
    }

    private function getLastStatusCode(array|null $http_response_header): int
    {
        if (!\is_array($http_response_header)) {
            return 500;
        }

        // Reverse the headers so we find the last HTTP status code if the request
        // was redirected See
        // http://php.net/manual/en/reserved.variables.httpresponseheader.php#122362
        $http_response_header = array_reverse($http_response_header);

        foreach ($http_response_header as $header) {
            if (preg_match('{^HTTP/.+ (\d{3}) }i', (string) $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 500;
    }
}
