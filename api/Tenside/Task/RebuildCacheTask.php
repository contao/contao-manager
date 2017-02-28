<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Tenside\Task;

use Contao\ManagerApi\Tenside\HomePathDeterminator;
use Symfony\Component\Filesystem\Filesystem;
use Tenside\Core\Config\TensideJsonConfig;
use Tenside\Core\Task\AbstractCliSpawningTask;
use Tenside\Core\Util\JsonArray;
use Tenside\Core\Util\ProcessBuilder;

/**
 * This class runs the cache clear command.
 */
class RebuildCacheTask extends AbstractCliSpawningTask
{
    /**
     * @var HomePathDeterminator
     */
    private $home;

    /**
     * @var TensideJsonConfig
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(HomePathDeterminator $home, TensideJsonConfig $config, JsonArray $file)
    {
        parent::__construct($file);

        $this->home = $home;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function doPerform()
    {
        $this->deleteCacheDirectory('prod');
        $this->runCacheClearCommand('prod');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'rebuild-cache';
    }

    /**
     * Manually removes the cache directory.
     *
     * @param string $environment
     */
    private function deleteCacheDirectory($environment)
    {
        (new Filesystem())->remove($this->home->homeDir().'/var/cache/'.$environment);
    }

    /**
     * Runs the cache clear command.
     *
     * @param string $environment
     */
    private function runCacheClearCommand($environment)
    {
        $process = ProcessBuilder::create($this->config->getPhpCliBinary())
            ->setArguments(['vendor/bin/contao-console', 'cache:clear', '--env='.$environment])
            ->setWorkingDirectory($this->home->homeDir())
            ->generate()
        ;

        $this->runProcess($process);
    }
}
