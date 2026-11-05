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
    /**
     * The ID of the task this configuration was loaded from or last saved as.
     *
     * For example, one request finishes task A and releases the manager lock. While
     * it builds the API response, another request deletes A and creates task B. The
     * first request may still save cloud output for A. Without this check, it would
     * write A's old configuration over B's task.json. If B has not been created yet,
     * it would bring the deleted task A back instead.
     *
     * Before saving, we check that task.json still belongs to the same task.
     */
    private string|null $persistedId = null;

    private bool $deleted = false;

    public function __construct(
        private readonly string $file,
        private readonly Filesystem $filesystem,
        Translator $translator,
        private readonly TaskLock $taskLock,
        string|null $name = null,
        array|null $options = null,
    ) {
        parent::__construct($file, $filesystem, $translator);

        $this->initialize();

        if (null === $name && null === $options) {
            $this->persistedId = $this->getId();

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

    /**
     * @throws TaskConflictException If this task was removed or replaced, or another task already exists
     */
    public function save(): void
    {
        if ($this->deleted) {
            return;
        }

        $this->withCurrent(
            function (): void {
                parent::save();
                $this->persistedId = $this->getId();
            },
        );
    }

    /**
     * @throws TaskConflictException If this task was removed or replaced
     */
    public function delete(): void
    {
        if ($this->deleted) {
            return;
        }

        $this->withCurrent(
            function (): void {
                parent::delete();
                $this->deleted = true;
            },
        );
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     *
     * @throws TaskConflictException If storage no longer matches this configuration
     */
    public function withCurrent(callable $operation): mixed
    {
        return $this->taskLock->synchronized(
            function () use ($operation): mixed {
                $this->assertCurrent();

                return $operation();
            },
        );
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

    /**
     * @throws TaskConflictException If this task was removed or replaced
     */
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
     *
     * @throws TaskConflictException If this task was removed or replaced
     */
    public function setCancelled(): void
    {
        $this->initialize();

        $this->data['cancelled'] = true;

        $this->save();
    }

    private function assertCurrent(): void
    {
        // The existence check does not cache local filesystem results.
        $exists = $this->filesystem->exists($this->file);

        if (null === $this->persistedId && !$exists) {
            return;
        }

        $data = $exists ? json_decode(file_get_contents($this->file), true) : null;

        if (null === $this->persistedId || !\is_array($data) || ($data['id'] ?? null) !== $this->persistedId) {
            throw new TaskConflictException();
        }
    }
}
