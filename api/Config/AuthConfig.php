<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Config;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Filesystem\Filesystem;

class AuthConfig extends AbstractConfig
{
    public function __construct(ApiKernel $kernel, Filesystem $filesystem = null)
    {
        $configFile = $kernel->getConfigDir().\DIRECTORY_SEPARATOR.'auth.json';

        parent::__construct($configFile, $filesystem);
    }

    /**
     * Returns the GitHub OAuth token from the config file.
     */
    public function getGithubToken(): ?string
    {
        if (!isset($this->data['github-oauth']['github.com'])) {
            return null;
        }

        return (string) $this->data['github-oauth']['github.com'];
    }

    /**
     * Stores the GitHub OAuth token in the config file.
     */
    public function setGithubToken(string $token): void
    {
        $this->data['github-oauth'] = [
            'github.com' => $token,
        ];

        $this->save();
    }

    /**
     * Adds basic authentication info for given domain.
     */
    public function setBasicAuth(string $domain, string $username, string $password): void
    {
        $this->data['http-basic'][$domain] = [
            'username' => $username,
            'password' => $password,
        ];

        $this->save();
    }

    /**
     * Deletes basic authentication for given domain.
     */
    public function deleteBasicAuth(string $domain): void
    {
        if (!isset($this->data['http-basic'][$domain])) {
            return;
        }

        unset($this->data['http-basic'][$domain]);

        $this->save();
    }
}
