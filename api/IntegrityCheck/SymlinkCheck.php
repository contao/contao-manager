<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;

class SymlinkCheck extends AbstractIntegrityCheck
{
    public function __construct(
        private readonly ApiKernel $kernel,
        private readonly Filesystem $filesystem,
        Translator $translator,
    ) {
        parent::__construct($translator);
    }

    public function run(): ApiProblem|null
    {
        if (null === ($error = $this->canCreateSymlinks())) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('symlink.title'),
            'https://php.net/symlink',
        ))->setDetail($error);
    }

    private function canCreateSymlinks(): string|null
    {
        if (!\function_exists('symlink')) {
            return '';
        }

        try {
            $tempname = $this->filesystem->tempnam(sys_get_temp_dir(), '');

            $this->filesystem->remove($tempname);
            $this->filesystem->symlink($this->kernel->getProjectDir(), $tempname);
            $this->filesystem->remove($tempname);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}
