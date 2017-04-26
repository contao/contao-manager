<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Filesystem\Filesystem;

class AuthConfig extends AbstractConfig
{
    /**
     * Constructor.
     *
     * @param ApiKernel  $kernel
     * @param Filesystem $filesystem
     */
    public function __construct(ApiKernel $kernel, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getManagerDir().DIRECTORY_SEPARATOR.'auth.json';

        parent::__construct($configFile, $filesystem);
    }

    /**
     * Stores the GitHub OAuth token in the config file.
     *
     * @param string $token
     */
    public function setGithubToken($token)
    {
        $this->data['github-oauth'] = [
            'github.com' => (string) $token,
        ];

        $this->save();
    }

    /**
     * Adds basic authentication info for given domain.
     *
     * @param string $domain
     * @param string $username
     * @param string $password
     */
    public function setBasicAuth($domain, $username, $password)
    {
        $this->data['http-basic'][(string) $domain] = [
            'username' => (string) $username,
            'password' => (string) $password,
        ];

        $this->save();
    }

    /**
     * Deletes basic authentication for given domain.
     *
     * @param string $domain
     */
    public function deleteBasicAuth($domain)
    {
        if (!isset($this->data['http-basic'][(string) $domain])) {
            return;
        }

        unset($this->data['http-basic'][(string) $domain]);

        $this->save();
    }
}
