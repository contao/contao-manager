<?php

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

class SelfUpdate
{
    const DOWNLOAD_URL = 'https://download.contao.org/contao-manager/%s/contao-manager.phar';
    const VERSION_URL = 'https://download.contao.org/contao-manager/%s/contao-manager.version';

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @var array
     */
    private $remote;

    private $checkedForUpdates = false;

    /**
     * Constructor.
     *
     * @param ApiKernel     $kernel
     * @param ManagerConfig $managerConfig
     */
    public function __construct(ApiKernel $kernel, ManagerConfig $managerConfig, Request $request)
    {
        $this->kernel = $kernel;
        $this->managerConfig = $managerConfig;
        $this->request = $request;
    }

    /**
     * Returns whether the current application can be updated.
     *
     * @return bool
     */
    public function canUpdate()
    {
        return '' !== \Phar::running(false);
    }

    /**
     * Returns whether the current environments supports the new requirements.
     *
     * @return bool
     */
    public function supportsUpdate()
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
     *
     * @return bool
     */
    public function isDev()
    {
        return $this->kernel->getVersion() === '@'.'package_version'.'@'
            || $this->kernel->getEnvironment() !== 'prod'
            || $this->kernel->isDebug()
        ;
    }

    /**
     * Gets the release channel for the current version.
     *
     * @return string
     */
    public function getChannel()
    {
        if ($this->isDev()) {
            return 'dev';
        }

        if (PHP_MAJOR_VERSION === 5) {
            return '1.1';
        }

        return 'stable';
    }

    /**
     * Returns whether there is an update available.
     *
     * @return bool
     */
    public function hasUpdate()
    {
        return $this->getOldVersion() !== $this->getNewVersion();
    }

    /**
     * Returns version of currently installed Phar.
     *
     * @return string
     */
    public function getOldVersion()
    {
        return $this->kernel->getVersion();
    }

    /**
     * Returns version of remotely available Phar.
     *
     * @return string
     */
    public function getNewVersion()
    {
        $this->checkForUpdate();

        return $this->managerConfig->get('latest_version');
    }

    /**
     * Returns the requirements for the remotely available Phar.
     *
     * @return array
     */
    public function getNewRequires()
    {
        $this->checkForUpdate();

        return $this->managerConfig->get('latest_requires', []);
    }

    /**
     * Updates the current Phar to the latest version available.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function update()
    {
        if (!$this->hasUpdate() || !$this->canUpdate() || !$this->supportsUpdate()) {
            return false;
        }

        $remote = $this->getRemoteInfo();

        $phar = \Phar::running(false);
        list($filename, $extension) = $this->splitFilename($phar);
        $backupFile = $this->kernel->getConfigDir().'/'.$filename.'-old'.$extension;
        $tempFile = dirname($phar).'/'.$filename.'.temp';

        $this->backup($phar, $backupFile);

        try {
            $this->download($tempFile);
            $this->validate($tempFile, $remote['sha1']);
            $this->install($tempFile, $phar);
        } catch (\Exception $e) {
            unlink($tempFile);
            throw $e;
        }

        // Check the update server after update.
        // This might be necessary if an updated version contains a new update URL,
        // which will be the case one the PHP version is no longer supported.
        $this->managerConfig->remove('last_update');

        return true;
    }

    /**
     * Loads latest information from the update server if the local cache has expired.
     */
    private function checkForUpdate()
    {
        if ($this->checkedForUpdates) {
            return;
        }

        $lastUpdate = $this->managerConfig->get('last_update');
        $latestVersion = $this->managerConfig->get('latest_version');

        if (!$this->isDev()
            && null !== $lastUpdate
            && null !== $latestVersion
            && false !== ($lastUpdate = strtotime($lastUpdate))
            && $lastUpdate <= time()
            && $lastUpdate > strtotime('-1 hour')
        ) {
            return;
        }

        $remote = $this->getRemoteInfo();

        $this->checkedForUpdates = true;
        $this->managerConfig->set('last_update', (new \DateTime())->format('c'));
        $this->managerConfig->set('latest_version', $remote['version']);
        $this->managerConfig->set('latest_requires', isset($remote['requires']) ? $remote['requires'] : []);
    }

    /**
     * Gets remote information about available updates.
     *
     * @return array
     */
    private function getRemoteInfo()
    {
        if (null === $this->remote) {
            $url = sprintf(self::VERSION_URL, $this->getChannel());
            $content = trim($this->request->get($url));
            $data = json_decode($content, true);

            if (!isset($data['version'], $data['sha1'])
                || !preg_match('@^\d+\.\d+\.\d+(-[a-z0-9\-]+)?$@', $data['version'])
                || !preg_match('%^[a-z0-9]{40}%', $data['sha1'])
            ) {
                throw new \RuntimeException('Version request returned incorrectly formatted response.');
            }

            $this->remote = $data;
        }

        return $this->remote;
    }

    /**
     * Creates a backup of the current Phar to the given target.
     *
     * @param string $current
     * @param string $target
     */
    private function backup($current, $target)
    {
        $result = copy($current, $target);

        if ($result === false) {
            throw new \RuntimeException(sprintf('Unable to backup %s to %s.', $current, $target));
        }
    }

    /**
     * Downloads the latest remote version to the given target.
     *
     * @param string $target
     */
    private function download($target)
    {
        $url = sprintf(self::DOWNLOAD_URL, $this->getChannel());

        $result = $this->request->getStream($url);

        if (false === $result) {
            throw new \RuntimeException(sprintf('Request to URL failed: %s', $url));
        }

        file_put_contents($target, $result);
    }

    /**
     * Validates temporary file if it matches the given SHA1 hash.
     *
     * @param string $tempFile
     * @param string $sha1
     */
    private function validate($tempFile, $sha1)
    {
        $tmpVersion = sha1_file($tempFile);
        if ($tmpVersion !== $sha1) {
            throw new \RuntimeException(
                sprintf(
                    'Download file appears to be corrupted or outdated. The file '
                    .'received does not have the expected SHA-1 hash: %s.',
                    $sha1
                )
            );
        }
    }

    /**
     * Installs the temporary Phar to the target location.
     *
     * @param string $tempFile
     * @param string $phar
     */
    private function install($tempFile, $phar)
    {
        rename($tempFile, $phar);
    }

    /**
     * Gets filename and extension from current Phar file.
     *
     * @param string $phar
     *
     * @return array
     */
    private function splitFilename($phar)
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
