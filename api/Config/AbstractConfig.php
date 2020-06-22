<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
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

            if (!\is_array($this->data)) {
                throw new \InvalidArgumentException('The config file does not contain valid JSON data.');
            }
        }
    }

    /**
     * Returns the config.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Returns the config keys.
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Replaces the current config by a new set.
     */
    public function replace(array $data = []): void
    {
        $this->data = $data;

        $this->save();
    }

    /**
     * Adds config options.
     */
    public function add(array $data = []): void
    {
        $this->data = array_replace($this->data, $data);

        $this->save();
    }

    /**
     * Returns a config option by name.
     */
    public function get(string $key, $default = null)
    {
        return \array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Sets a config option by name.
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;

        $this->save();
    }

    /**
     * Returns true if the config option is defined.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * Removes a config option.
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);

        $this->save();
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        return \count($this->data);
    }

    /**
     * Saves current data to the JSON config file.
     */
    protected function save(): void
    {
        $this->filesystem->dumpFile(
            $this->configFile,
            json_encode($this->data, JSON_PRETTY_PRINT)
        );
    }
}
