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
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Task\TaskConfig;
use Contao\ManagerApi\TaskOperation\AbstractInlineOperation;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class RemoveUploadsOperation extends AbstractInlineOperation
{
    /**
     * @var array
     */
    private $uploads;

    /**
     * @var UploadsConfig
     */
    private $uploadsConfig;

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

    public function __construct(array $uploads, UploadsConfig $uploadsConfig, TaskConfig $taskConfig, Environment $environment, Translator $translator, Filesystem $filesystem = null)
    {
        parent::__construct($taskConfig);

        $this->uploads = $uploads;
        $this->uploadsConfig = $uploadsConfig;
        $this->environment = $environment;
        $this->translator = $translator;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function getSummary(): string
    {
        return $this->translator->trans('taskoperation.remove-uploads.summary');
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

    protected function doRun(): bool
    {
        foreach ($this->uploads as $config) {
            $this->uploadsConfig->remove($config['id']);

            try {
                $this->filesystem->remove($this->environment->getUploadDir().'/'.$config['id']);
            } catch (IOException $e) {
                // Ignore if file could not be deleted
            }
        }

        return true;
    }

    protected function getName(): string
    {
        return 'remove-uploads';
    }
}
