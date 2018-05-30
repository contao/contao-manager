<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Task\Composer;

use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\AbstractTask;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\Composer\ClearCacheOperation;

class ClearCacheTask extends AbstractTask
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     */
    public function __construct(ConsoleProcessFactory $processFactory, Translator $translator)
    {
        $this->processFactory = $processFactory;

        parent::__construct($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'clear_cache';
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
        return [
            new ClearCacheOperation($this->processFactory, $this->translator),
        ];
    }
}
