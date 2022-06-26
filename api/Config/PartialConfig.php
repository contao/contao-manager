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

class PartialConfig implements \IteratorAggregate, \Countable
{
    /**
     * @var AbstractConfig
     */
    private $parent;

    /**
     * @var string
     */
    private $key;

    public function __construct(AbstractConfig $parent, string $key)
    {
        $this->parent = $parent;
        $this->key = $key;
    }

    /**
     * Returns the config.
     */
    public function all(): array
    {
        return $this->parent->get($this->key, []);
    }

    /**
     * Returns the config keys.
     */
    public function keys(): array
    {
        return array_keys($this->parent->get($this->key, []));
    }

    /**
     * Replaces the current config by a new set.
     */
    public function replace(array $data = []): void
    {
        $this->parent->set($this->key, $data);
    }

    /**
     * Adds config options.
     */
    public function add(array $data = []): void
    {
        $this->replace(
            array_replace($this->all(), $data)
        );
    }

    /**
     * Returns a config option by name.
     *
     * @param mixed|null $default
     */
    public function get(string $key, $default = null)
    {
        $data = $this->all();

        return \array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * Sets a config option by name.
     */
    public function set(string $key, $value): void
    {
        $this->replace([$key => $value]);
    }

    /**
     * Returns true if the config option is defined.
     */
    public function has(string $key): bool
    {
        $data = $this->all();

        return \array_key_exists($key, $data);
    }

    /**
     * Removes a config option.
     */
    public function remove(string $key): void
    {
        $data = $this->all();

        unset($data[$key]);

        $this->replace($data);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    public function count(): int
    {
        return \count($this->all());
    }
}
