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

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Repository\ArtifactRepository;
use Composer\Repository\PathRepository;
use Composer\Repository\PlatformRepository;
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

    public function __construct(ApiKernel $kernel, ManagerConfig $managerConfig, Filesystem $filesystem)
    {
        $this->kernel = $kernel;
        $this->managerConfig = $managerConfig;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Resets the Composer object (necessary after modifying Composer files).
     */
    public function reset(): void
    {
        $this->composer = null;
    }

    /**
     * Returns whether debug mode is activated.
     */
    public function isDebug(): bool
    {
        return $this->kernel->isDebug();
    }

    /**
     * Gets path to the directory where all Contao Manager related information is stored.
     */
    public function getBackupDir(): string
    {
        return $this->kernel->getConfigDir();
    }

    /**
     * Gets path to the composer.json file in the Contao root.
     */
    public function getJsonFile(): string
    {
        return $this->kernel->getProjectDir().\DIRECTORY_SEPARATOR.'composer.json';
    }

    /**
     * Gets path to the composer.lock file in the Contao root.
     */
    public function getLockFile(): string
    {
        return $this->kernel->getProjectDir().\DIRECTORY_SEPARATOR.'composer.lock';
    }

    /**
     * Gets the directory where Composer installs its packages to.
     */
    public function getVendorDir(): string
    {
        return $this->kernel->getProjectDir().\DIRECTORY_SEPARATOR.'vendor';
    }

    /**
     * Gets the directory where uploads are stored to.
     * These are temporary and only until they are installed as artifact or provider.
     */
    public function getUploadDir(): string
    {
        $dir = $this->kernel->getConfigDir().'/uploads';

        $this->filesystem->mkdir($dir);

        return $dir;
    }

    /**
     * Gets the path where artifacts are installed to.
     * Artifacts are ZIP files that contain Composer packages.
     *
     * @see https://getcomposer.org/doc/05-repositories.md#artifact
     */
    public function getArtifactDir(): string
    {
        $dir = $this->kernel->getConfigDir().'/packages';

        $this->filesystem->mkdir($dir);

        return $dir;
    }

    /**
     * Gets list of file names in the artifacts directory.
     */
    public function getArtifacts(): array
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
     */
    public function getComposer($reload = false): Composer
    {
        if (null === $this->composer || $reload) {
            $this->composer = Factory::create(new NullIO(), $this->getJsonFile());
        }

        return $this->composer;
    }

    /**
     * Gets whether the Cloud resolver is enabled in the Manager configuration.
     */
    public function useCloudResolver(): bool
    {
        return !$this->managerConfig->get('disable_cloud', false);
    }

    public function getComposerJsonFile(): JsonFile
    {
        $file = $this->getComposer()->getConfig()->getConfigSource()->getName();

        return new JsonFile($file);
    }

    public function getComposerJson(): array
    {
        $json = $this->getComposerJsonFile()->read();

        $repositories = $this->getComposer()->getConfig()->getRepositories();
        unset($repositories['packagist.org']);

        if (!empty($repositories) || !empty($json['repositories'])) {
            $json['repositories'] = array_values($repositories);
        }

        return $json;
    }

    public function getComposerLock(): array
    {
        $locker = $this->getComposer()->getLocker();

        if (!$locker->isLocked()) {
            return [];
        }

        return $locker->getLockData();
    }

    public function getPlatformPackages(): array
    {
        $platformOverrides = $this->getComposer()->getConfig()->get('platform');
        $platform = [];

        foreach ((new PlatformRepository([], $platformOverrides))->getPackages() as $package) {
            if ('composer-plugin-api' === $package->getName()) {
                continue;
            }

            $platform[$package->getName()] = $package->getVersion();
        }

        return $platform;
    }

    public function getLocalPackages(): array
    {
        $packages = [];
        $repositories = $this->getComposer()->getRepositoryManager()->getRepositories();
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

    public function hasPackage(string $packageName): bool
    {
        try {
            $json = $this->getComposerJson();

            return isset($json['require'][$packageName]);
        } catch (\Exception $exception) {
            return false;
        }
    }
}
