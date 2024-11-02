<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Composer;

use Composer\Util\Filesystem;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractProcessOperation;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INSTALL')]
class CreateProjectOperation extends AbstractProcessOperation
{
    /**
     * @var string
     */
    private $publicDir;

    public function __construct(
        TaskConfig $taskConfig,
        ConsoleProcessFactory $processFactory,
        ApiKernel $kernel,
        private readonly Environment $environment,
        private readonly string $package,
        private readonly string|null $version = null,
        bool $isUpload = false,
    ) {
        try {
            parent::__construct($processFactory->restoreBackgroundProcess('composer-create-project'));
        } catch (\Exception) {
            $folder = uniqid('contao-');

            $arguments = [
                'composer',
                'create-project',
                $this->package.($this->version ? ':'.$this->version : ''),
                $folder,
                '--no-install',
                '--no-scripts',
                '--no-dev',
                '--no-progress',
                '--no-ansi',
                '--no-interaction',
            ];

            if ($isUpload) {
                $arguments[] = '--repository='.json_encode(['type' => 'artifact', 'url' => $this->environment->getArtifactDir()]);
            }

            if ($this->environment->isDebug()) {
                $arguments[] = '--profile';
                $arguments[] = '-vvv';
            }

            $process = $processFactory->createManagerConsoleBackgroundProcess(
                $arguments,
                'composer-create-project',
            );
            $process->setMeta(['folder' => $folder]);

            parent::__construct($process);
        }

        $this->publicDir = $taskConfig->getState('public-dir');

        if (null !== $this->publicDir) {
            return;
        }

        if ($kernel->getProjectDir() === $kernel->getPublicDir()) {
            throw new \RuntimeException('Cannot install without a public directory.');
        }

        $taskConfig->setState('public-dir', $this->publicDir = $kernel->getPublicDir());
    }

    public function getSummary(): string
    {
        return 'composer create-project '.$this->package.($this->version ? ':'.$this->version : '');
    }

    public function run(): void
    {
        parent::run();

        if ($this->process->isSuccessful() && !$this->isInstalled()) {
            $folder = $this->process->getMeta()['folder'] ?? null;

            if ($folder) {
                $fs = new Filesystem();
                $files = Finder::create()
                    ->exclude(['__MACOSX'])
                    ->notName(['theme.xml', '.DS_Store'])
                    ->ignoreVCS(true)
                    ->ignoreDotFiles(true)
                    ->depth(0)
                    ->in($folder)
                ;

                foreach ($files as $file) {
                    $fs->copy(
                        $file->getPathname(),
                        \dirname($file->getPath()).\DIRECTORY_SEPARATOR.$file->getFilename(),
                    );
                }

                $fs->removeDirectory($folder);

                // write public-dir in composer.json
                try {
                    $file = $this->environment->getComposerJsonFile();
                    $json = $file->read();
                    $json['extra']['public-dir'] = basename($this->publicDir);
                    $file->write($json);
                } catch (\RuntimeException) {
                    // ignore
                }

                $this->process->setMeta(['installed' => true]);
            }
        }
    }

    public function isRunning(): bool
    {
        return parent::isRunning() || ($this->isStarted() && !$this->hasError() && !$this->isInstalled());
    }

    public function isSuccessful(): bool
    {
        return parent::isSuccessful() && $this->isInstalled();
    }

    private function isInstalled(): bool
    {
        return (bool) ($this->process->getMeta()['installed'] ?? false);
    }
}
