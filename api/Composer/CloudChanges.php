<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Composer;

class CloudChanges
{
    private array $require = [];

    private array $remove = [];

    private array $updates = [];

    private bool $dryRun = false;

    public function requirePackage(string $packageName, string $version = null): void
    {
        unset($this->remove[$packageName]);

        $this->require[$packageName] = $version ? $packageName.'='.$version : $packageName;

        $this->addUpdate($packageName);
    }

    public function getRequiredPackages(): array
    {
        return $this->require;
    }

    public function removePackage(string $packageName): void
    {
        unset($this->require[$packageName]);

        $this->remove[$packageName] = $packageName;

        $this->addUpdate($packageName);
    }

    public function getRemovedPackages(): array
    {
        return $this->remove;
    }

    public function setUpdates(array $updates): void
    {
        $this->updates = [];

        foreach ($updates as $packageName) {
            $this->updates[$packageName] = $packageName;
        }
    }

    public function addUpdate(string $packageName): void
    {
        $this->updates[$packageName] = $packageName;
    }

    public function getUpdates(): array
    {
        return array_values($this->updates);
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    public function getDryRun(): bool
    {
        return $this->dryRun;
    }
}
