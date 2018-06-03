<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Tenside;

use Contao\ManagerApi\ApiKernel;
use Tenside\Core\Util\HomePathDeterminator as BaseHomePathDeterminator;

class HomePathDeterminator extends BaseHomePathDeterminator
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

        parent::__construct($kernel->getContaoDir());
    }

    public function tensideDataDir()
    {
        return $this->kernel->getManagerDir();
    }
}
