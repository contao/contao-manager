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

use Contao\ManagerApi\Config\AbstractConfig;
use Contao\ManagerApi\I18n\Translator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;

class TaskConfig extends AbstractConfig
{
    public function __construct(string $file, Filesystem $filesystem, Translator $translator, string|null $name = null, array|null $options = null)
    {
        parent::__construct($file, $filesystem, $translator);

        $this->initialize();

        if (null === $name && null === $options) {
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

    public function getId(): string
    {
        $this->initialize();

        return $this->data['id'] ?? '--unknown--';
    }

    public function getName(): string
    {
        $this->initialize();

        return $this->data['name'] ?? '--unknown--';
    }

    public function getOptions(): array
    {
        $this->initialize();

        return $this->data['options'] ?? [];
    }

    public function getOption(string $name, array|bool|float|int|string|null $default = null): array|bool|float|int|string|null
    {
        $this->initialize();

        return \array_key_exists($name, $this->data['options']) ? $this->data['options'][$name] : $default;
    }

    public function getState(string $name, array|bool|float|int|string|null $default = null): array|bool|float|int|string|null
    {
        $this->initialize();

        return \array_key_exists($name, $this->data['state']) ? $this->data['state'][$name] : $default;
    }

    public function setState(string $name, array|bool|float|int|string|null $value): void
    {
        $this->initialize();

        $this->data['state'][$name] = $value;

        $this->save();
    }

    public function clearState(string $name): void
    {
        $this->initialize();

        unset($this->data['state'][$name]);
    }

    public function isCancelled(): bool
    {
        $this->initialize();

        return (bool) $this->data['cancelled'];
    }

    /**
     * Mark task as cancelled.
     */
    public function setCancelled(): void
    {
        $this->initialize();

        $this->data['cancelled'] = true;

        $this->save();
    }
}
