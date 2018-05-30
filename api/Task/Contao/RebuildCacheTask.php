<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Task\Contao;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\Contao\CacheClearOperation;
use Contao\ManagerApi\TaskOperation\Contao\CacheWarmupOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveCacheOperation;
use Symfony\Component\Filesystem\Filesystem;

class RebuildCacheTask extends AbstractTask
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ApiKernel             $environment
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(
        ApiKernel $environment,
        ConsoleProcessFactory $processFactory,
        Translator $translator,
        Filesystem $filesystem
    ) {
        $this->kernel = $environment;
        $this->processFactory = $processFactory;
        $this->filesystem = $filesystem;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'rebuild_cache';
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskConfig $config)
    {
        return parent::create($config)->setAutoClose(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOperations(TaskConfig $config)
    {
        $operations = [
            new RemoveCacheOperation($config->getOption('environment', 'prod'), $this->kernel, $config, $this->translator, new Filesystem()),
            new CacheClearOperation($this->processFactory, $this->translator, $config->getOption('environment', 'prod')),
        ];

        if (false !== $config->getOption('warmup', true)) {
            $operations[] = new CacheWarmupOperation($this->processFactory, $this->translator, $config->getOption('environment', 'prod'));
        }

        return $operations;
    }
}
