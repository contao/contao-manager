<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;

class Environment
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Constructor.
     *
     * @param ApiKernel     $kernel
     * @param ManagerConfig $managerConfig
     */
    public function __construct(ApiKernel $kernel, ManagerConfig $managerConfig)
    {
        $this->kernel = $kernel;
        $this->managerConfig = $managerConfig;
    }

    public function getAll()
    {
        return [
            $this->getJsonFile(),
            $this->getLockFile(),
            $this->getVendorDir(),
        ];
    }

    public function isDebug()
    {
        return $this->kernel->isDebug();
    }

    public function getBackupDir()
    {
        return $this->kernel->getConfigDir();
    }

    public function getJsonFile()
    {
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'composer.json';
    }

    public function getLockFile()
    {
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'composer.lock';
    }

    public function getVendorDir()
    {
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'vendor';
    }

    public function getComposer()
    {
        if (null === $this->composer) {
            $this->composer = Factory::create(new NullIO(), $this->getJsonFile());
        }

        return $this->composer;
    }

    public function useCloudResolver()
    {
        return !$this->managerConfig->get('disable_cloud', false);
    }
}
