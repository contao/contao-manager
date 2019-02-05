<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Composer;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Repository\ArtifactRepository;
use Composer\Repository\PathRepository;
use Composer\Repository\PlatformRepository;

class CloudChanges
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var JsonFile
     */
    private $json;

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

    /**
     * Constructor.
     *
     * @param string $file
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;

        $file = $composer->getConfig()->getConfigSource()->getName();

        $this->json = new JsonFile($file);
    }

    public function requirePackage($packageName, $version = null)
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

    public function removePackage($packageName)
    {
        $this->remove[] = $packageName;
    }

    public function getRemovedPackages()
    {
        return $this->remove;
    }

    public function setUpdates(array $updates)
    {
        $this->updates = $updates;
    }

    public function getUpdates()
    {
        return $this->updates;
    }

    public function setDryRun($dryRun)
    {
        $this->dryRun = (bool) $dryRun;
    }

    public function getDryRun()
    {
        return $this->dryRun;
    }

    /**
     * @return JsonFile
     */
    public function getJsonFile()
    {
        return $this->json;
    }

    public function getJson()
    {
        $json = $this->getJsonFile()->read();

        $repositories = $this->composer->getConfig()->getRepositories();
        unset($repositories['packagist.org']);

        $json['repositories'] = array_values($repositories);

        return $json;
    }

    public function getLock()
    {
        $locker = $this->composer->getLocker();

        if (!$locker->isLocked()) {
            return [];
        }

        return $locker->getLockData();
    }

    public function getPlatform()
    {
        $platformOverrides = $this->composer->getConfig()->get('platform');
        $platform = [];

        foreach ((new PlatformRepository([], $platformOverrides))->getPackages() as $package) {
            if ('composer-plugin-api' === $package->getName()) {
                continue;
            }

            $platform[$package->getName()] = $package->getVersion();
        }

        return $platform;
    }

    public function getLocalPackages()
    {
        $packages = [];
        $repositories = $this->composer->getRepositoryManager()->getRepositories();
        $dumper = new ArrayDumper();

        foreach ($repositories as $repository) {
            if ($repository instanceof ArtifactRepository || $repository instanceof PathRepository) {
                foreach ($repository->getPackages() as $package) {
                    $dump = $dumper->dump($package);

                    // see https://github.com/composer/composer/issues/7955
                    unset($dump['dist']['reference']);

                    $packages[] = $dump;
                }
            }
        }

        return $packages;
    }
}
