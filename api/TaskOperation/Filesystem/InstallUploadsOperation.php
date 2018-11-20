<?php

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
use Contao\ManagerApi\Task\TaskStatus;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
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

    public function updateStatus(TaskStatus $status)
    {
        $status->setSummary($this->translator->trans('taskoperation.install-uploads.summary'));

        $installed = $this->taskConfig->getState($this->getName().'.files');

        if (!empty($installed)) {
            $status->addConsole(
                implode('', array_map(
                    function ($upload) {
                        return '- '.$this->translator->trans('taskoperation.install-uploads.console', $upload);
                    },
                    $installed
                ))
            );
        }

        $this->addConsoleStatus($status);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRun()
    {
        $installed = [];

        foreach ($this->uploads as $config) {
            $target = $this->getArtifactName($config['name']);

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

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'install-uploads';
    }

    private function getArtifactName($name)
    {
        // TODO do not overwrite if name exists
        return $name;
    }
}
