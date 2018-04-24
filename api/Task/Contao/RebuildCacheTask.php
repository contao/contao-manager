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
use Contao\ManagerApi\TaskOperation\TaskOperationInterface;
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
     * @var TaskOperationInterface[]
     */
    private $operations;

    /**
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(
        ApiKernel $kernel,
        ConsoleProcessFactory $processFactory,
        Translator $translator,
        Filesystem $filesystem
    ) {
        $this->kernel = $kernel;
        $this->processFactory = $processFactory;
        $this->filesystem = $filesystem;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function createInitialStatus(TaskConfig $config)
    {
        return new TaskStatus($this->translator->trans('task.rebuild_cache.title'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperations(TaskConfig $config)
    {
        if (null === $this->operations) {
            $this->operations = [
                new RemoveCacheOperation($config->getOption('environment', 'prod'), $this->kernel, $config, new Filesystem()),
                new CacheClearOperation($this->processFactory),
                new CacheWarmupOperation($this->processFactory),
            ];
        }

        return $this->operations;
    }
}
