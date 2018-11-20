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
use Composer\Factory;
use Composer\IO\NullIO;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Environment
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Constructor.
     *
     * @param ApiKernel     $kernel
     * @param ManagerConfig $managerConfig
     */
    public function __construct(ApiKernel $kernel, ManagerConfig $managerConfig, Filesystem $filesystem)
    {
        $this->kernel = $kernel;
        $this->managerConfig = $managerConfig;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function getAll()
    {
        return [
            $this->getJsonFile(),
            $this->getLockFile(),
            $this->getVendorDir(),
        ];
    }

    public function isDebug()
    {
        return $this->kernel->isDebug();
    }

    public function getBackupDir()
    {
        return $this->kernel->getConfigDir();
    }

    /**
     * Gets path to the composer.json file in the Contao root.
     *
     * @return string
     */
    public function getJsonFile()
    {
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'composer.json';
    }

    /**
     * Gets path to the composer.lock file in the Contao root.
     *
     * @return string
     */
    public function getLockFile()
    {
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'composer.lock';
    }

    /**
     * Gets the directory where Composer installs its packages to.
     *
     * @return string
     */
    public function getVendorDir()
    {
        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'vendor';
    }

    /**
     * Gets the directory where uploads are stored to.
     * These are temporary and only until they are installed as artifact or provider.
     *
     * @return string
     */
    public function getUploadDir()
    {
        $dir = $this->kernel->getConfigDir().'/uploads';

        $this->filesystem->mkdir($dir);

        return $dir;
    }

    /**
     * Gets the path where artifacts are installed to.
     * Artifacts are ZIP files that contain Composer packages.
     * @see https://getcomposer.org/doc/05-repositories.md#artifact
     *
     * @return string
     */
    public function getArtifactDir()
    {
        $dir = $this->kernel->getConfigDir().'/packages';

        $this->filesystem->mkdir($dir);

        return $dir;
    }

    /**
     * Gets list of file names in the artifacts directory.
     *
     * @return array
     */
    public function getArtifacts()
    {
        $files = [];
        $finder = (new Finder())
            ->files()
            ->depth(0)
            ->in($this->getArtifactDir())
        ;

        foreach ($finder->getIterator() as $file) {
            $files[] = $file->getFilename();
        }

        return $files;
    }

    /**
     * Gets the Composer instance.
     *
     * @return Composer
     */
    public function getComposer()
    {
        if (null === $this->composer) {
            $this->composer = Factory::create(new NullIO(), $this->getJsonFile());
        }

        return $this->composer;
    }

    /**
     * Gets whether the Cloud resolver is enabled in the Manager configuration.
     *
     * @return bool
     */
    public function useCloudResolver()
    {
        return !$this->managerConfig->get('disable_cloud', false);
    }
}
