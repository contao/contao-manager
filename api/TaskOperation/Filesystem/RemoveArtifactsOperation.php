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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INSTALL')]
class RemoveArtifactsOperation extends AbstractInlineOperation
{
    public function __construct(
        private readonly array $files,
        TaskConfig $taskConfig,
        private readonly Environment $environment,
        private readonly Translator $translator,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct($taskConfig);
    }

    public function getSummary(): string
    {
        return $this->translator->trans('taskoperation.remove-artifacts.summary');
    }

    public function getDetails(): string|null
    {
        return implode(', ', $this->files);
    }

    protected function doRun(): bool
    {
        foreach ($this->files as $file) {
            try {
                $this->filesystem->remove($this->environment->getArtifactDir().'/'.$file);
            } catch (IOException) {
                // Ignore if file could not be deleted
            }
        }

        return true;
    }

    protected function getName(): string
    {
        return 'remove-artifacts';
    }
}
