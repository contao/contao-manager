<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\SelfUpdate;

use Contao\ManagerApi\ApiKernel;

class Updater
{
    const DOWNLOAD_URL = 'https://download.contao.org/contao-manager.phar.php';
    const VERSION_URL = 'https://download.contao.org/contao-manager.version';

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var array
     */
    private $remote;

    /**
     * Constructor.
     *
     * @param ApiKernel $kernel
     */
    public function __construct(ApiKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Returns whether the current application can be updated.
     *
     * @return bool
     */
    public function canUpdate()
    {
        return '' !== \Phar::running(false)
            && $this->kernel->getVersion() !== '@'.'package_version'.'@'
            && $this->kernel->getEnvironment() === 'prod'
            && !$this->kernel->isDebug()
        ;
    }

    /**
     * Returns whether there is an update available.
     *
     * @return bool
     */
    public function hasUpdate()
    {
        return $this->canUpdate() && $this->getOldVersion() !== $this->getNewVersion();
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
        $remote = $this->getRemoteInfo();

        return $remote['version'];
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
        if (!$this->hasUpdate()) {
            return false;
        }

        $remote = $this->getRemoteInfo();

        $phar = \Phar::running(false);
        $filename = basename(basename($phar, '.phar'), '.php');
        $backupFile = $this->kernel->getManagerDir().'/'.$filename.'-old.phar.php';
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

        return true;
    }

    /**
     * Gets remote information about available updates.
     *
     * @return array
     */
    private function getRemoteInfo()
    {
        if (null === $this->remote) {
            $content = trim(file_get_contents(self::VERSION_URL));
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
        $result = file_get_contents(self::DOWNLOAD_URL);

        if (false === $result) {
            throw new \RuntimeException(sprintf('Request to URL failed: %s', self::DOWNLOAD_URL));
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
}
