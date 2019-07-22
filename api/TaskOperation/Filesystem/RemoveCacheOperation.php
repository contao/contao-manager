<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class RemoveCacheOperation extends AbstractInlineOperation
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param string $environment
     */
    public function __construct($environment, ApiKernel $kernel, TaskConfig $taskConfig, Translator $translator, Filesystem $filesystem)
    {
        $this->environment = $environment;
        $this->kernel = $kernel;
        $this->translator = $translator;
        $this->filesystem = $filesystem;

        parent::__construct($taskConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(): bool
    {
        $this->filesystem->remove($this->getCacheDir());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status): void
    {
        $status->setSummary($this->translator->trans('taskoperation.remove-cache.summary'));
        $status->setDetail($this->getCacheDir());

        $this->addConsoleStatus($status);
    }

    protected function getName(): string
    {
        return 'remove-cache@'.$this->getCacheDir();
    }

    /**
     * Gets the Contao cache directory for current environment.
     *
     * @return string
     */
    private function getCacheDir()
    {
        return $this->kernel->getProjectDir().'/var/cache/'.$this->environment;
    }
}
