<?php

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
use Contao\ManagerApi\TaskOperation\Contao\InstallLockOperation;
use Contao\ManagerApi\TaskOperation\Contao\InstallUnlockOperation;
use Symfony\Component\Filesystem\Filesystem;

class InstallToolTask extends AbstractTask
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
        return 'contao/install-tool';
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskConfig $config)
    {
        return parent::create($config)->setAutoClose(true);
    }

    protected function getTitle()
    {
        return $this->translator->trans('task.install_tool.title');
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOperations(TaskConfig $config)
    {
        if (true === $config->getOption('lock')) {
            return [new InstallLockOperation($this->processFactory, $this->translator)];
        }

        if (false === $config->getOption('lock')) {
            return [new InstallUnlockOperation($this->processFactory, $this->translator)];
        }

        throw new \RuntimeException('Missing lock config argument.');
    }
}
