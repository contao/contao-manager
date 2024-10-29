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

use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;

class TaskConfig
{
    private array $data;

    private bool $isDeleted = false;

    public function __construct(
        private readonly string $file,
        private readonly Filesystem $filesystem,
        string|null $name = null,
        array|null $options = null,
    ) {
        if (null === $name && null === $options) {
            $data = json_decode(file_get_contents($this->file), true);

            if (!\is_array($data)) {
                throw new \RuntimeException(\sprintf('Invalid task data in file "%s"', $this->file));
            }

            $this->data = $data;

            return;
        }

        $this->data = [
            'id' => Uuid::uuid4()->toString(),
            'name' => $name,
            'options' => $options,
            'state' => [],
            'cancelled' => false,
        ];
    }

    public function getId(): string|null
    {
        return $this->data['id'] ?? '--unknown--';
    }

    public function getName(): string|null
    {
        return $this->data['name'];
    }

    public function getOptions(): array|null
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

    public function save(): bool
    {
        if ($this->isDeleted) {
            return false;
        }

        $this->filesystem->dumpFile(
            $this->file,
            json_encode($this->data, JSON_PRETTY_PRINT)
        );

        return true;
    }

    public function delete(): void
    {
        $this->isDeleted = true;
        $this->filesystem->remove($this->file);
    }
}
