<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\System;

use Composer\Repository\PlatformRepository;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;
use Symfony\Component\Filesystem\Filesystem;

class SelfUpdate
{
    private const DOWNLOAD_URL = 'https://download.contao.org/contao-manager/%s/contao-manager.phar';

    private const VERSION_URL = 'https://download.contao.org/contao-manager/%s/contao-manager.version';

    private array|null $remote = null;

    private bool $checkedForUpdates = false;

    public function __construct(
        private readonly ApiKernel $kernel,
        private readonly ManagerConfig $managerConfig,
        private readonly Request $request,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * Returns whether the current application can be updated.
     */
    public function canUpdate(): bool
    {
        return ApiKernel::isPhar();
    }

    /**
     * Returns whether the current environments supports the new requirements.
     */
    public function supportsUpdate(): bool
    {
        $this->checkForUpdate();

        $requires = $this->managerConfig->get('latest_requires', []);
        $repository = new PlatformRepository();

        foreach ($requires as $name => $constraint) {
            if (null === $repository->findPackage($name, $constraint)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether this is a development build.
     */
    public function isDev(): bool
    {
        // @phpstan-ignore-next-line
        return ApiKernel::VERSION_KEY === ApiKernel::MANAGER_VERSION
            || 'prod' !== $this->kernel->getEnvironment()
            || $this->kernel->isDebug();
    }

    /**
     * Gets the release channel for the current version.
     */
    public function getChannel(): string
    {
        return $this->isDev() ? 'dev' : 'stable';
    }

    /**
     * Returns whether there is an update available.
     */
    public function hasUpdate(): bool
    {
        return $this->getOldVersion() !== $this->getNewVersion();
    }

    /**
     * Returns version of currently installed Phar.
     */
    public function getOldVersion(): string
    {
        return ApiKernel::MANAGER_VERSION;
    }

    /**
     * Returns version of remotely available Phar.
     */
    public function getNewVersion(): string|null
    {
        $this->checkForUpdate();

        return $this->managerConfig->has('latest_version') ? (string) $this->managerConfig->get('latest_version') : null;
    }

    /**
     * Returns the requirements for the remotely available Phar.
     */
    public function getNewRequires(): array
    {
        $this->checkForUpdate();

        return $this->managerConfig->get('latest_requires', []);
    }

    /**
     * Updates the current Phar to the latest version available.
     *
     * @throws \Throwable
     */
    public function update(): bool
    {
        if (!$this->hasUpdate() || !$this->canUpdate() || !$this->supportsUpdate()) {
            return false;
        }

        $remote = $this->getRemoteInfo();

        $phar = \Phar::running(false);
        [$filename, $extension] = $this->splitFilename($phar);
        $backupFile = $this->kernel->getConfigDir().'/'.$filename.'-old'.$extension;
        $tempFile = \dirname($phar).'/'.$filename.'.temp';

        $this->backup($phar, $backupFile);

        try {
            $this->download($tempFile);
            $this->validate($tempFile, $remote['sha1']);
            $this->install($tempFile, $phar);
        } catch (\Throwable $throwable) {
            $this->filesystem->remove($tempFile);

            throw $throwable;
        }

        // Check the update server after update. This might be necessary if an updated
        // version contains a new update URL, which will be the case one the PHP version
        // is no longer supported.
        $this->managerConfig->remove('last_update');

        return true;
    }

    /**
     * Loads latest information from the update server if the local cache has expired.
     */
    private function checkForUpdate(): void
    {
        if ($this->checkedForUpdates) {
            return;
        }

        $lastUpdate = $this->managerConfig->get('last_update');
        $latestVersion = $this->managerConfig->get('latest_version');

        if (
            null !== $latestVersion
            && null !== $lastUpdate
            && false !== ($lastUpdate = strtotime((string) $lastUpdate))
            && !$this->isDev()
            && $lastUpdate <= time()
            && $lastUpdate > strtotime('-1 hour')
        ) {
            return;
        }

        $remote = $this->getRemoteInfo();

        $this->checkedForUpdates = true;
        $this->managerConfig->set('last_update', (new \DateTime())->format('c'));
        $this->managerConfig->set('latest_version', $remote['version']);
        $this->managerConfig->set('latest_requires', $remote['requires'] ?? []);
    }

    /**
     * Gets remote information about available updates.
     */
    private function getRemoteInfo(): array
    {
        if (null === $this->remote) {
            $url = \sprintf(self::VERSION_URL, $this->getChannel());
            $content = trim((string) $this->request->get($url, $statusCode, false, 0));
            $data = json_decode($content, true);

            if (
                !isset($data['version'], $data['sha1'])
                || !preg_match('@^\d+\.\d+\.\d+(-[a-z0-9\-]+)?$@', (string) $data['version'])
                || !preg_match('%^[a-z0-9]{40}%', (string) $data['sha1'])
            ) {
                throw new \RuntimeException('Version request returned incorrectly formatted response.');
            }

            $this->remote = $data;
        }

        return $this->remote;
    }

    /**
     * Creates a backup of the current Phar to the given target.
     */
    private function backup(string $current, string $target): void
    {
        $this->filesystem->copy($current, $target, true);
    }

    /**
     * Downloads the latest remote version to the given target.
     */
    private function download(string $target): void
    {
        $url = \sprintf(self::DOWNLOAD_URL, $this->getChannel());

        $result = $this->request->getStream($url);

        if (null === $result) {
            throw new \RuntimeException(\sprintf('Request to URL failed: %s', $url));
        }

        $this->filesystem->dumpFile($target, $result);
    }

    /**
     * Validates temporary file if it matches the given SHA1 hash.
     */
    private function validate(string $tempFile, string $sha1): void
    {
        $tmpVersion = sha1_file($tempFile);

        if ($tmpVersion !== $sha1) {
            throw new \RuntimeException(\sprintf('Download file appears to be corrupted or outdated. The file received does not have the expected SHA-1 hash: %s.', $sha1));
        }
    }

    /**
     * Installs the temporary Phar to the target location.
     */
    private function install(string $tempFile, string $phar): void
    {
        if (\defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->filesystem->copy($tempFile, $phar, true);
            $this->filesystem->remove($tempFile);

            return;
        }

        $this->filesystem->rename($tempFile, $phar, true);
    }

    /**
     * Gets filename and extension from current Phar file.
     */
    private function splitFilename(string $phar): array
    {
        $extension = '.phar.php';
        $filename = basename($phar, $extension);

        if ($filename === $phar) {
            $extension = '.phar';
            $filename = basename($phar, $extension);
        }

        return [$filename, $extension];
    }
}
