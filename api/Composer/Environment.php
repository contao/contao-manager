<?php

namespace Contao\ManagerApi\Composer;

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

    public function getManagerDir()
    {
        return $this->kernel->getManagerDir();
    }

    public function getContaoDir()
    {
        return $this->kernel->getContaoDir();
    }

    public function getJsonFile()
    {
        return $this->kernel->getContaoDir().'/composer.json';
    }

    public function getLockFile()
    {
        return $this->kernel->getContaoDir().'/composer.lock';
    }

    public function getVendorDir()
    {
        return $this->kernel->getContaoDir().'/vendor';
    }

    public function useCloudResolver()
    {
        return !$this->managerConfig->get('disable_cloud', false);
    }
}
