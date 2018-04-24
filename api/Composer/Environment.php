<?php

namespace Contao\ManagerApi\Composer;

use Contao\ManagerApi\ApiKernel;

class Environment
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * Constructor.
     *
     * @param ApiKernel $kernel
     */
    public function __construct(ApiKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getAll()
    {
        return [
            $this->getJsonFile(),
            $this->getLockFile(),
            $this->getVendorDir(),
        ];
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
}
