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

use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\I18n\Translator;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @implements \IteratorAggregate<string, array|string|int|float|bool>
 */
abstract class AbstractConfig implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, array|string|int|float|bool>|null
     */
    protected array|null $data = null;

    private bool $deleted = false;

    public function __construct(
        private readonly string $file,
        private readonly Filesystem $filesystem,
        private readonly Translator $translator,
    ) {
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
     */
    public function get(string $key, array|bool|float|int|string|null $default = null): array|bool|float|int|string|null
    {
        $this->initialize();

        return \array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Sets a config option by name.
     */
    public function set(string $key, array|bool|float|int|string $value): void
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

    /**
     * @return \ArrayIterator<string, array|string|int|float|bool>
     */
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
    public function save(): void
    {
        if ($this->deleted || null === $this->data) {
            return;
        }

        try {
            $this->filesystem->dumpFile(
                $this->file,
                json_encode($this->data, JSON_PRETTY_PRINT),
            );
        } catch (IOException $exception) {
            $this->throwNotWritable($exception);
        }
    }

    public function delete(): void
    {
        $this->deleted = true;

        try {
            $this->filesystem->remove($this->file);
        } catch (IOException $exception) {
            $this->throwNotWritable($exception);
        }
    }

    protected function initialize(): void
    {
        if (null !== $this->data) {
            return;
        }

        if (!$this->filesystem->exists($this->file)) {
            $this->data = [];

            return;
        }

        $data = json_decode(file_get_contents($this->file), true);

        if (!\is_array($data)) {
            throw new \InvalidArgumentException('The config file does not contain valid JSON data.');
        }

        $this->data = $data;
    }

    private function throwNotWritable(\Throwable $throwable): void
    {
        $problem = (new ApiProblem(
            $this->translator->trans('error.writable.config-file', ['file' => $this->file]),
            'https://php.net/is_writable',
        ))->setDetail($this->translator->trans('error.writable.detail'));

        throw new ApiProblemException($problem, $throwable);
    }
}
