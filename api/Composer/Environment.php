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
use Contao\ManagerApi\Config\ComposerConfig;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\System\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Environment
{
    private Composer|null $composer = null;

    private \Composer\Util\Filesystem|null $composerFs = null;

    public function __construct(
        private readonly ApiKernel $kernel,
        private readonly ManagerConfig $managerConfig,
        private readonly ComposerConfig $composerConfig,
        private readonly Filesystem $filesystem,
        private readonly Request $request,
        private readonly LoggerInterface|null $logger = null,
    ) {
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

    public function getBackupPaths(): array
    {
        return [
            $this->getJsonFile() => \sprintf('%s/%s~', $this->getBackupDir(), basename($this->getJsonFile())),
            $this->getLockFile() => \sprintf('%s/%s~', $this->getBackupDir(), basename($this->getLockFile())),
        ];
    }

    public function createBackup(): bool
    {
        if (!$this->filesystem->exists($this->getJsonFile())) {
            if (null !== $this->logger) {
                $this->logger->info('Cannot create composer file backup, source JSON does not exist', ['file' => $this->getJsonFile()]);
            }

            return false;
        }

        if (null !== $this->logger) {
            $this->logger->info('Creating backup of composer files');
        }

        foreach ($this->getBackupPaths() as $source => $target) {
            if ($this->filesystem->exists($source)) {
                $this->filesystem->copy($source, $target, true);

                if (null !== $this->logger) {
                    $this->logger->info(\sprintf('Copied "%s" to "%s"', $source, $target));
                }
            } elseif (null !== $this->logger) {
                $this->logger->info(\sprintf('File "%s" does not exist', $source));
            }
        }

        return true;
    }

    public function restoreBackup(): bool
    {
        if (null !== $this->logger) {
            $this->logger->info('Restoring backup of composer files');
        }

        foreach (array_flip($this->getBackupPaths()) as $source => $target) {
            if ($this->filesystem->exists($source)) {
                $this->filesystem->copy($source, $target, true);
                $this->filesystem->remove($source);

                if (null !== $this->logger) {
                    $this->logger->info(\sprintf('Copied "%s" to "%s"', $source, $target));
                }
            } elseif (null !== $this->logger) {
                $this->logger->info(\sprintf('File "%s" does not exist', $source));
            }
        }

        return true;
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
     * Gets the directory where uploads are stored to. These are temporary and only
     * until they are installed as artifact or provider.
     */
    public function getUploadDir(): string
    {
        $dir = $this->kernel->getConfigDir().'/uploads';

        $this->filesystem->mkdir($dir);

        return $dir;
    }

    /**
     * Gets the path where artifacts are installed to. Artifacts are ZIP files that
     * contain Composer packages.
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
    public function getComposer(bool $reload = false): Composer
    {
        $this->composerConfig->allowPlugins();

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

        if ([] !== $repositories || !empty($json['repositories'])) {
            $json['repositories'] = [];

            foreach ($repositories as $repository) {
                if (isset($repository['url'])) {
                    $repository['url'] = $this->normalizeRepositoryPath($repository['url']);
                }

                $json['repositories'][] = $repository;
            }
        }

        return $json;
    }

    public function getComposerLockFile(): JsonFile
    {
        return new JsonFile($this->getLockFile());
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
            if (\in_array($package->getName(), ['composer-plugin-api', 'composer-runtime-api'], true)) {
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

                    if (isset($dump['dist']['path'])) {
                        $dump['dist']['path'] = $this->normalizeRepositoryPath($dump['dist']['path']);
                    }

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
        } catch (\Exception) {
            return false;
        }
    }

    public function mergeMetadata(array $package, string|null $language = null): array
    {
        if (isset($package['source']) || preg_match('{https?://}', $package['dist']['url'] ?? '') || empty($package['extra']['contao-metadata-url'])) {
            return $package;
        }

        try {
            $headers = [];

            if ($language) {
                $headers[] = 'Accept-Language: '.$language;
            }

            if ($package['version'] ?? null) {
                $headers[] = 'Contao-Package-Version: '.$package['version'];
            }

            $metadata = JsonFile::parseJson(
                $this->request->getJson($package['extra']['contao-metadata-url'], $headers),
                $package['extra']['contao-metadata-url'],
            );

            if (\is_array($metadata)) {
                // Make sure only allowed keys are preserved in metadata
                $metadata = array_intersect_key($metadata, array_flip([
                    'title',
                    'description',
                    'homepage',
                    'suggest',
                    'support',
                    'funding',
                    'abandoned',
                    'logo',
                ]));

                // Do not allow custom logo URLs since they could track the Contao Manager user/browser
                if (isset($metadata['logo']) && preg_match('{https?://}i', (string) $metadata['logo'])) {
                    unset($metadata['logo']);
                }

                return array_merge($package, $metadata, ['private' => true]);
            }
        } catch (\Exception) {
        }

        return $package;
    }

    private function normalizeRepositoryPath(string $path): string
    {
        if (null === $this->composerFs) {
            $this->composerFs = new \Composer\Util\Filesystem();
        }

        $normalizedPath = $this->composerFs->normalizePath($path);

        if (str_starts_with($path, './')) {
            return './'.$normalizedPath;
        }

        return $normalizedPath;
    }
}
