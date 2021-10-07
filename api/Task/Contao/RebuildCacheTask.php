<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Contao;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
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

    public function getName(): string
    {
        return 'contao/rebuild-cache';
    }

    public function create(TaskConfig $config): TaskStatus
    {
        return parent::create($config)->setAutoClose(true);
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.rebuild_cache.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        $operations = [
            new RemoveCacheOperation($config->getOption('environment', 'prod'), $this->kernel, $config, $this->filesystem),
            new CacheClearOperation($this->processFactory, $config->getOption('environment', 'prod')),
        ];

        if (false !== $config->getOption('warmup', true)) {
            $operations[] = new CacheWarmupOperation($this->processFactory, $config->getOption('environment', 'prod'));
        } else {
            // Remove cache directory again (contao/contao-manager#655)
            $operations[] = new RemoveCacheOperation($config->getOption('environment', 'prod'), $this->kernel, $config, $this->filesystem, 'remove-cache-again');
        }

        return $operations;
    }
}
