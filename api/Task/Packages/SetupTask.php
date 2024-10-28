<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Task\Packages;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Composer\CloudChanges;
use Contao\ManagerApi\Composer\CloudResolver;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\Composer\CloudOperation;
use Contao\ManagerApi\TaskOperation\Composer\CreateProjectOperation;
use Contao\ManagerApi\TaskOperation\Composer\InstallOperation;
use Contao\ManagerApi\TaskOperation\Contao\CreateContaoOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\InstallUploadsOperation;
use Contao\ManagerApi\TaskOperation\Filesystem\RemoveUploadsOperation;
use Symfony\Component\Filesystem\Filesystem;

class SetupTask extends AbstractPackagesTask
{
    public function __construct(private readonly ConsoleProcessFactory $processFactory, private readonly CloudResolver $cloudResolver, private readonly ApiKernel $kernel, private readonly UploadsConfig $uploads, Environment $environment, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($environment, $filesystem, $translator);
    }

    public function getName(): string
    {
        return 'contao/install';
    }

    protected function getTitle(): string
    {
        return $this->translator->trans('task.setup_packages.title');
    }

    protected function buildOperations(TaskConfig $config): array
    {
        $upload = null;

        if ($uploadId = $config->getOption('upload')) {
            $upload = $config->getState('upload');

            if (!$upload) {
                $upload = $this->uploads->get($uploadId);
                $config->setState('upload', $upload);
            }

            $operations = [
                new InstallUploadsOperation(
                    [$upload],
                    $config,
                    $this->environment,
                    $this->translator,
                    $this->filesystem
                ),
                new CreateProjectOperation($config, $this->processFactory, $this->kernel, $this->environment, $upload['package']['name'], null, true),
            ];
        } elseif ($package = $config->getOption('package')) {
            $operations = [new CreateProjectOperation($config, $this->processFactory, $this->kernel, $this->environment, $package, $config->getOption('version'))];
        } else {
            $operations = [new CreateContaoOperation($config, $this->environment, $this->kernel, $this->filesystem)];
        }

        if ($config->getOption('no-update')) {
            return $operations;
        }

        if ($this->environment->useCloudResolver()) {
            $operations[] = new CloudOperation(
                $this->cloudResolver,
                new CloudChanges(),
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem
            );
        }

        $operations[] = new InstallOperation($this->processFactory, $config, $this->environment, $this->translator, false, !$config->isCancelled());

        if ($upload) {
            $operations[] = new RemoveUploadsOperation(
                [$upload],
                $this->uploads,
                $config,
                $this->environment,
                $this->translator,
                $this->filesystem
            );
        }

        return $operations;
    }
}
