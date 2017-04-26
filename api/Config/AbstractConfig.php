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

abstract class AbstractConfig implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $data = [];

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
     * Returns the config.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Returns the config keys.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Replaces the current config by a new set.
     *
     * @param array $data
     */
    public function replace(array $data = [])
    {
        $this->data = $data;

        $this->save();
    }

    /**
     * Adds config options.
     *
     * @param array $data
     */
    public function add(array $data = [])
    {
        $this->data = array_replace($this->data, $data);

        $this->save();
    }

    /**
     * Returns a config option by name.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Sets a config option by name.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;

        $this->save();
    }

    /**
     * Returns true if the config option is defined.
     *
     * @param string $key The key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Removes a config option.
     *
     * @param string $key The key
     */
    public function remove($key)
    {
        unset($this->data[$key]);

        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->data);
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
