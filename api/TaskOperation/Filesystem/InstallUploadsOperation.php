<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation\Filesystem;

use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Contao\ManagerApi\TaskOperation\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

class InstallUploadsOperation extends AbstractInlineOperation
{
    /**
     * @var array
     */
    private $uploads;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(array $uploads, TaskConfig $config, Environment $environment, Translator $translator, Filesystem $filesystem = null)
    {
        parent::__construct($config);

        $this->uploads = $uploads;
        $this->environment = $environment;
        $this->translator = $translator;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function getSummary(): string
    {
        return $this->translator->trans('taskoperation.install-uploads.summary');
    }

    public function getDetails(): ?string
    {
        $files = array_map(
            static function ($config) {
                return $config['name'];
            },
            $this->uploads
        );

        return implode(', ', $files);
    }

    public function getConsole(): ConsoleOutput
    {
        $console = new ConsoleOutput();

        if (!$this->isSuccessful()) {
            return $console;
        }

        $installed = $this->taskConfig->getState($this->getName().'.files');

        if (!empty($installed)) {
            $console->add(
                implode('', array_map(
                    function ($upload) {
                        return '- '.$this->translator->trans('taskoperation.install-uploads.console', $upload);
                    },
                    $installed
                ))
            );
        }

        return $this->addConsoleOutput($console);
    }

    protected function doRun(): bool
    {
        $installed = [];

        foreach ($this->uploads as $config) {
            $target = basename($config['package']['dist']['url']);

            // Ignore if a file is already installed, so it's not deleted on failed operation
            if ($this->filesystem->exists($this->environment->getArtifactDir().'/'.$target)) {
                continue;
            }

            $this->filesystem->copy(
                $this->environment->getUploadDir().'/'.$config['id'],
                $this->environment->getArtifactDir().'/'.$target
            );

            $installed[$target] = [
                'name' => $target,
                'package' => $config['package']['name'],
                'version' => $config['package']['version'],
            ];
        }

        $this->taskConfig->setState($this->getName().'.files', $installed);

        return true;
    }

    protected function getName(): string
    {
        return 'install-uploads';
    }
}
