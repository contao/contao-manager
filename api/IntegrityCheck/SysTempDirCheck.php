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

use Contao\ManagerApi\I18n\Translator;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class SysTempDirCheck extends AbstractIntegrityCheck
{
    public function __construct(
        private readonly Filesystem $filesystem,
        Translator $translator,
    ) {
        parent::__construct($translator);
    }

    public function run(): ApiProblem|null
    {
        $tempdir = rtrim(sys_get_temp_dir(), '/');
        $subdir = $tempdir.'/'.md5(__DIR__);

        if (!$this->canWriteFileInDirectory($tempdir) || !$this->canWriteFileInDirectory($subdir, true)) {
            return (new ApiProblem(
                $this->trans('systempdir.title'),
                'https://php.net/open_basedir',
            ))->setDetail($this->trans('systempdir.detail'));
        }

        return null;
    }

    private function canWriteFileInDirectory(string $path, bool $createDirectory = false): bool
    {
        if ($createDirectory) {
            try {
                $this->filesystem->remove($path);
            } catch (IOException) {
            }

            try {
                $this->filesystem->mkdir($path);
            } catch (IOException) {
            }
        }

        $file = $path.'/'.md5(__FILE__);

        try {
            $this->filesystem->touch($file);
            $result = is_writable($file);
        } catch (IOException) {
            $result = false;
        }

        try {
            $this->filesystem->remove($file);
        } catch (IOException) {
        }

        if ($createDirectory) {
            try {
                $this->filesystem->remove($path);
            } catch (IOException) {
            }
        }

        return $result;
    }
}
