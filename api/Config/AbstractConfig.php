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

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Exception\ApiProblemException;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractConfig implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $data = [];

    private readonly Filesystem $filesystem;

    private bool $initialized = false;

    public function __construct(
        private readonly string $fileName,
        private readonly ApiKernel $kernel,
        Filesystem $filesystem = null,
    ) {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Returns the config.
     */
    public function all(): array
    {
        $this->initialize();

        return $this->data;
    }

    /**
     * Returns the config keys.
     */
    public function keys(): array
    {
        $this->initialize();

        return array_keys($this->data);
    }

    /**
     * Replaces the current config by a new set.
     */
    public function replace(array $data = []): void
    {
        $this->initialize();

        $this->data = $data;

        $this->save();
    }

    /**
     * Adds config options.
     */
    public function add(array $data = []): void
    {
        $this->initialize();

        $this->data = array_replace($this->data, $data);

        $this->save();
    }

    /**
     * Returns a config option by name.
     *
     * @param mixed|null $default
     */
    public function get(string $key, $default = null)
    {
        $this->initialize();

        return \array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Sets a config option by name.
     */
    public function set(string $key, $value): void
    {
        $this->initialize();

        $this->data[$key] = $value;

        $this->save();
    }

    /**
     * Returns true if the config option is defined.
     */
    public function has(string $key): bool
    {
        $this->initialize();

        return \array_key_exists($key, $this->data);
    }

    /**
     * Removes a config option.
     */
    public function remove(string $key): void
    {
        $this->initialize();

        unset($this->data[$key]);

        $this->save();
    }

    public function getIterator(): \ArrayIterator
    {
        $this->initialize();

        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        $this->initialize();

        return \count($this->data);
    }

    /**
     * Saves current data to the JSON config file.
     */
    protected function save(): void
    {
        if (!$this->initialized) {
            return;
        }

        $file = $this->kernel->getConfigDir().\DIRECTORY_SEPARATOR.$this->fileName;

        try {
            $this->filesystem->dumpFile(
                $file,
                json_encode($this->data, JSON_PRETTY_PRINT)
            );
        } catch (IOException $exception) {
            $translator = $this->kernel->getTranslator();
            $problem = (new ApiProblem(
                $translator->trans('error.writable.config-file', ['file' => $file]),
                'https://php.net/is_writable'
            ))->setDetail($translator->trans('error.writable.detail'));

            throw new ApiProblemException($problem, $exception);
        }
    }

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        $file = $this->kernel->getConfigDir().\DIRECTORY_SEPARATOR.$this->fileName;

        if (is_file($file)) {
            $this->data = json_decode(file_get_contents($file), true);

            if (!\is_array($this->data)) {
                throw new \InvalidArgumentException('The config file does not contain valid JSON data.');
            }
        }
    }
}
