<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Composer;

use Composer\Command\RemoveCommand;
use Composer\Command\RequireCommand;
use Composer\Config;
use Composer\Factory;
use Composer\Installer\InstallationManager;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Locker;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;

class CloudChanges
{
    /**
     * @var string
     */
    private $file;

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
    public function __construct($file)
    {
        $this->file = $file;
        $this->json = new JsonFile($file);
    }

    public function requirePackage($packageName, $version = null)
    {
        if ($version) {
            $this->require[] = $packageName.' '.$version;
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
        if (empty($this->require) && empty($this->remove)) {
            return $this->json->read();
        }

        $filesystem = new Filesystem();
        $tempdir = sys_get_temp_dir().'/'.uniqid('composer-', false);
        $tempfile = $tempdir.'/'.basename($this->json->getPath());

        putenv('COMPOSER='.$tempfile);
        $filesystem->copy($this->json->getPath(), $tempfile);
        $composer = Factory::create(new NullIO());

        if (!empty($this->require)) {
            $command = new RequireCommand();
            $command->setComposer($composer);
            $command->getDefinition()->addOption(new InputOption('--no-plugins', null, InputOption::VALUE_NONE, 'Whether to disable plugins.'));
            $input = new ArrayInput(
                [
                    'packages' => $this->require,
                    '--no-update' => true,
                ],
                $command->getDefinition()
            );

            $command->run($input, new NullOutput());
        }

        if (!empty($this->remove)) {
            $command = new RemoveCommand();
            $command->setComposer($composer);
            $command->getDefinition()->addOption(new InputOption('--no-plugins', null, InputOption::VALUE_NONE, 'Whether to disable plugins.'));
            $input = new ArrayInput(
                [
                    'packages' => $this->remove,
                    '--no-update' => true,
                ],
                $command->getDefinition()
            );

            $command->run($input, new NullOutput());
        }

        $json = (new JsonFile($tempfile))->read();
        $filesystem->remove($tempdir);

        return $json;
    }

    public function getLock()
    {
        $locker = new Locker(
            new NullIO(),
            new JsonFile(basename($this->json->getPath()).'/composer.lock'),
            new RepositoryManager(new NullIO(), new Config()),
            new InstallationManager(),
            file_get_contents($this->json->getPath())
        );

        if (!$locker->isLocked() || !$locker->isFresh()) {
            return [];
        }

        return $locker->getLockData();
    }

    public function getPlatform()
    {
        $json = $this->json->read();
        $overrides = isset($json['config']['platform']) ? $json['config']['platform'] : [];
        $platform = [];

        foreach ((new PlatformRepository([], $overrides))->getPackages() as $package) {
            if ('composer-plugin-api' === $package->getName()) {
                continue;
            }

            $platform[$package->getName()] = $package->getVersion();
        }

        return $platform;
    }
}
