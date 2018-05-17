<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Filesystem;

class DumpPackagesOperation extends AbstractInlineOperation
{
    /**
     * @var CloudChanges
     */
    private $changes;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param CloudChanges $changes
     * @param Filesystem   $filesystem
     */
    public function __construct(CloudChanges $changes, Filesystem $filesystem, TaskConfig $taskConfig)
    {
        $this->changes = $changes;
        $this->filesystem = $filesystem;

        parent::__construct($taskConfig);
    }

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary('Updating composer.json …');

        $requires = $this->changes->getRequiredPackages();
        $removes = $this->changes->getRemovedPackages();

        if (!empty($requires)) {
            $status->addConsole("> Added packages to composer.json\n - ".implode("\n - ", $requires));
        }

        if (!empty($removes)) {
            $status->addConsole("> Removed packages from composer.json\n - ".implode("\n - ", $removes));
        }

        $this->addConsoleStatus($status);
    }

    protected function getName()
    {
        return 'dump-packages';
    }

    protected function doRun()
    {
        $this->changes->getJsonFile()->write($this->changes->getJson());

        return true;
    }
}
