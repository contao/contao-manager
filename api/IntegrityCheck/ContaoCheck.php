<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ContaoApi;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Tenside\Core\Task\TaskList;

class ContaoCheck extends AbstractIntegrityCheck
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ContaoApi
     */
    private $contaoApi;

    /**
     * @var TaskList
     */
    private $tasks;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ApiKernel       $kernel
     * @param ContaoApi       $contaoApi
     * @param Translator      $translator
     * @param TaskList        $tasks
     * @param Filesystem|null $filesystem
     */
    public function __construct(ApiKernel $kernel, ContaoApi $contaoApi, Translator $translator, TaskList $tasks, Filesystem $filesystem = null)
    {
        parent::__construct($translator);

        $this->kernel = $kernel;
        $this->contaoApi = $contaoApi;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->tasks = $tasks;
    }

    public function run()
    {
        if (!empty($this->tasks->getIds())) {
            return null;
        }

        if (!$this->hasInstallation()) {
            return null;
        }

        $version = $this->getContaoVersion();

        if (null === $version) {
            return (new ApiProblem(
                $this->trans('contao_unknown.title')
            ))->setDetail($this->trans('contao_unknown.detail'));
        }

        if (version_compare($version, '4.3.5', '>=')) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('contao_old.title')
        ))->setDetail($this->trans('contao_old.detail', ['version' => $version]));
    }

    /**
     * Checks if any "unknown" files are found in the web directory.
     *
     * @return bool
     */
    private function hasInstallation()
    {
        $content = scandir($this->kernel->getContaoDir());
        $content = array_diff($content, ['.', '..', 'contao-manager', 'web', '.htaccess']);

        return !empty($content);
    }

    /**
     * Tries to detect the Contao 4/3/2 version by analyzing the filesystem.
     *
     * @return null|string
     */
    private function getContaoVersion()
    {
        if ($this->filesystem->exists($this->kernel->getContaoDir().'/vendor/bin/contao-console')) {
            return $this->contaoApi->getContaoVersion();
        }

        // Required for Contao 2.11
        define('TL_ROOT', $this->kernel->getContaoDir());

        $files = [
            $this->kernel->getContaoDir().'/system/constants.php',
            $this->kernel->getContaoDir().'/system/config/constants.php',
        ];

        // Test if the Phar was placed in the Contao 2/3 root
        if ('' !== ($phar = \Phar::running())) {
            $files[] = dirname(substr($phar, 7)).'/system/constants.php';
            $files[] = dirname(substr($phar, 7)).'/system/config/constants.php';
        }

        foreach ($files as $file) {
            if ($this->filesystem->exists($file)) {
                try {
                    @include $file;
                } catch (\Error $e) {
                    // do nothing on error in PHP 7 or Symfony Polyfill
                } catch (\Exception $e) {
                    // do nothing on exception
                }

                if (defined('VERSION') && defined('BUILD')) {
                    return VERSION.'.'.BUILD;
                }

                break;
            }
        }

        return null;
    }
}
