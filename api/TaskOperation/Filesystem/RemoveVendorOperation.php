<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class RemoveVendorOperation extends AbstractInlineOperation
{
    /**
     * @var Environment|string
     */
    private $environment;

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
     * @param TaskConfig  $taskConfig
     * @param Environment $environment
     * @param Translator  $translator
     * @param Filesystem  $filesystem
     */
    public function __construct(TaskConfig $taskConfig, Environment $environment, Translator $translator, Filesystem $filesystem)
    {
        parent::__construct($taskConfig);

        $this->environment = $environment;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun()
    {
        $this->filesystem->remove($this->environment->getVendorDir());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.remove-vendor.summary'));
        $status->setDetail($this->environment->getVendorDir());

        $this->addConsoleStatus($status);
    }

    protected function getName()
    {
        return 'remove-vendor';
    }
}
