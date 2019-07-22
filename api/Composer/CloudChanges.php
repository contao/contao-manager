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
    /**
     * @var array
     */
    private $require = [];

    /**
     * @var array
     */
    private $remove = [];

    /**
     * @var array
     */
    private $updates = [];

    /**
     * @var bool
     */
    private $dryRun = false;

    public function requirePackage($packageName, $version = null): void
    {
        if ($version) {
            $this->require[] = $packageName.'='.$version;
        } else {
            $this->require[] = $packageName;
        }
    }

    public function getRequiredPackages()
    {
        return $this->require;
    }

    public function removePackage($packageName): void
    {
        $this->remove[] = $packageName;
    }

    public function getRemovedPackages()
    {
        return $this->remove;
    }

    public function setUpdates(array $updates): void
    {
        $this->updates = $updates;
    }

    public function getUpdates()
    {
        return $this->updates;
    }

    public function setDryRun($dryRun): void
    {
        $this->dryRun = (bool) $dryRun;
    }

    public function getDryRun()
    {
        return $this->dryRun;
    }
}
