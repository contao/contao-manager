<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\System;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContaoController extends Controller
{
    /**
     * Gets response about Composer configuration and file validation.
     *
     * @return Response
     */
    public function __invoke()
    {
        if (!$this->get('contao_manager.config.manager')->has('server')
            || !$this->get('contao_manager.system.server_info')->getPhpExecutable()
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        $problem = $this->get('contao_manager.integrity.contao')->run();
        if ($problem instanceof ApiProblem) {
            throw new ApiProblemException($problem);
        }

        if (0 === count($files = $this->getProjectFiles())) {
            return new JsonResponse(
                [
                    'version' => null,
                    'api' => null,
                ]
            );
        }

//        try {
            return new JsonResponse(
                [
                    'version' => $this->getContaoVersion(),
                    'api' => $this->getApiVersion(),
                ]
            );
//        } catch (\RuntimeException $e) {
//            if ($e instanceof ProcessFailedException || $e instanceof ProcessOutputException) {
//                return new JsonResponse(
//                    [
//                        'message' => $e->getMessage(),
//                        'exitCode' => $e->getProcess()->getExitCode(),
//                        'output' => $e->getProcess()->getOutput(),
//                        'errorOutput' => $e->getProcess()->getErrorOutput(),
//                    ],
//                    Response::HTTP_INTERNAL_SERVER_ERROR
//                );
//            }
//
//            throw $e;
//        }
    }

    /**
     * Gets a list of files in the project root directory, excluding what is allowed to install Contao.
     *
     * @return array
     */
    private function getProjectFiles()
    {
        $content = scandir($this->get('kernel')->getContaoDir(), SCANDIR_SORT_NONE);
        $content = array_diff($content, ['.', '..', 'cgi-bin', 'contao-manager', 'web', '.htaccess', '.DS_Store']);

        return $content;
    }

    private function getApiVersion()
    {
        $filesystem = $this->get('filesystem');

        if (!$filesystem->exists($this->get('contao_manager.process.console_factory')->getContaoApiPath())) {
            return 0;
        }

        return $this->get('contao_manager.process.contao_api')->getApiVersion();
    }

    /**
     * Tries to detect the Contao 4/3/2 version by analyzing the filesystem.
     *
     * @return null|string
     */
    private function getContaoVersion()
    {
        $filesystem = $this->get('filesystem');

        if ($filesystem->exists($this->get('contao_manager.process.console_factory')->getContaoConsolePath())) {
            return $this->get('contao_manager.process.contao_api')->getContaoVersion();
        }

        /** @var ApiKernel $kernel */
        $kernel = $this->get('kernel');

        // Required for Contao 2.11
        define('TL_ROOT', $kernel->getContaoDir());

        $files = [
            $kernel->getContaoDir().'/system/constants.php',
            $kernel->getContaoDir().'/system/config/constants.php',
        ];

        // Test if the Phar was placed in the Contao 2/3 root
        if ('' !== ($phar = \Phar::running())) {
            $files[] = dirname(substr($phar, 7)).'/system/constants.php';
            $files[] = dirname(substr($phar, 7)).'/system/config/constants.php';
        }

        foreach ($files as $file) {
            if ($filesystem->exists($file)) {
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

        return 'UNKNOWN';
    }
}
