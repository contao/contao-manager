<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Config;

use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractConfig
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    private $configFile;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param string     $configFile
     * @param Filesystem $filesystem
     */
    public function __construct($configFile, Filesystem $filesystem = null)
    {
        $this->configFile = $configFile;
        $this->filesystem = $filesystem ?: new Filesystem();

        if (is_file($this->configFile)) {
            $this->data = json_decode(file_get_contents($this->configFile), true);

            if (!is_array($this->data)) {
                throw new \InvalidArgumentException('The config file does not contain valid JSON data.');
            }
        }
    }

    /**
     * Gets whether the config is empty (file does not exist).
     *
     * @return bool
     */
    public function isEmpty()
    {
        return null === $this->data;
    }

    /**
     * Saves current data to the JSON config file.
     */
    protected function save()
    {
        $this->filesystem->dumpFile(
            $this->configFile,
            json_encode($this->data, JSON_PRETTY_PRINT)
        );
    }
}
