<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task;

use Symfony\Component\Filesystem\Filesystem;

class TaskConfig
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var Filesystem|null
     */
    private $filesystem;

    /**
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param string $file
     * @param null   $name
     */
    public function __construct(string $file, string $name = null, array $options = null, Filesystem $filesystem = null)
    {
        $this->file = $file;
        $this->filesystem = $filesystem ?: new Filesystem();

        $this->data = [
            'name' => $name,
            'options' => $options,
            'state' => [],
            'cancelled' => false,
        ];

        if (null === $name && null === $options) {
            $this->data = json_decode(file_get_contents($file), true);

            if (!\is_array($this->data)) {
                throw new \RuntimeException(sprintf('Invalid task data in file "%s"', $file));
            }
        }
    }

    public function getName(): ?string
    {
        return $this->data['name'];
    }

    public function getOptions(): ?array
    {
        return $this->data['options'];
    }

    public function getOption(string $name, $default = null)
    {
        return \array_key_exists($name, $this->data['options']) ? $this->data['options'][$name] : $default;
    }

    public function getState(string $name, $default = null)
    {
        return \array_key_exists($name, $this->data['state']) ? $this->data['state'][$name] : $default;
    }

    public function setState(string $name, $value): void
    {
        $this->data['state'][$name] = $value;

        $this->save();
    }

    public function clearState(string $name): void
    {
        unset($this->data['state'][$name]);
    }

    public function isCancelled(): bool
    {
        return (bool) $this->data['cancelled'];
    }

    /**
     * Mark task as cancelled.
     */
    public function setCancelled(): void
    {
        $this->data['cancelled'] = true;

        $this->save();
    }

    public function save(): void
    {
        file_put_contents(
            $this->file,
            json_encode($this->data),
            LOCK_EX
        );
    }

    public function delete(): void
    {
        $this->filesystem->remove($this->file);
    }
}
