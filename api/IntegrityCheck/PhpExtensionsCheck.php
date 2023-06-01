<?php

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
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PhpExtensionsCheck extends AbstractIntegrityCheck
{
    /**
     * @var ApiKernel
     */
    private $kernel;
    private $filesystem;

    private static $extensions = [
        'intl',
        'dom',
        'xmlreader',
        'openssl',
    ];

    public function __construct(ApiKernel $kernel, Translator $translator)
    {
        parent::__construct($translator);

        $this->kernel = $kernel;
        $this->filesystem = new Filesystem();
    }


    public function run()
    {
        if (!$this->isContao4()) {
            return null;
        }

        foreach (self::$extensions as $extension) {
            if (($problem = $this->checkExtension($extension)) !== null) {
                return $problem;
            }
        }

        return null;
    }

    private function checkExtension($extension)
    {
        if (extension_loaded($extension)) {
            return null;
        }

        return (new ApiProblem(
            $this->trans($extension.'.title'),
            'https://php.net/'.$extension
        ))->setDetail($this->trans($extension.'.detail'));
    }

    /**
     * Tries to detect the Contao 4/3/2 version by analyzing the filesystem.
     *
     * @return boolean
     */
    private function isContao4()
    {
        $projectDir = $this->kernel->getProjectDir();

        if ($this->filesystem->exists($projectDir.'/vendor/contao/core-bundle/bin/contao-console')) {
            return true;
        }

        // Required for Contao 2.11
        define('TL_ROOT', $projectDir);

        $files = [
            $projectDir.'/system/constants.php',
            $projectDir.'/system/config/constants.php',
        ];

        // Test if the Phar was placed in the Contao 2/3 root
        if ('' !== ($phar = \Phar::running(false))) {
            $files[] = dirname($phar).'/system/constants.php';
            $files[] = dirname($phar).'/system/config/constants.php';
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
                    return constant('VERSION') < 4;
                }

                break;
            }
        }

        return true;
    }
}
