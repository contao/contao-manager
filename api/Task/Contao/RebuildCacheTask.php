<?php

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
    protected function buildOperations(TaskConfig $config)
    {
        return [
            new RemoveCacheOperation($config->getOption('environment', 'prod'), $this->kernel, $config, new Filesystem()),
            new CacheClearOperation($this->processFactory),
            new CacheWarmupOperation($this->processFactory),
        ];
    }
}
