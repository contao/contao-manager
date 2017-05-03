<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\I18n\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class ManagerConfigController extends ConfigController
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ManagerConfig $config
     * @param Translator    $translator
     */
    public function __construct(ManagerConfig $config, Translator $translator)
    {
        parent::__construct($config);

        $this->translator = $translator;
    }

    public function putAction(Request $request)
    {
        return $this->validatePhpExecutable($request) ?: parent::putAction($request);
    }

    public function patchAction(Request $request)
    {
        return $this->validatePhpExecutable($request) ?: parent::patchAction($request);
    }

    /**
     * @param Request $request
     */
    private function validatePhpExecutable(Request $request)
    {
        if (!$request->request->has('php_cli')) {
            return null;
        }

        $cli = escapeshellcmd($request->request->get('php_cli'));

        $process = new Process($cli." -r 'echo PHP_VERSION;'");

        $process->run();

        if (!$process->isSuccessful()) {
            return new JsonResponse(
                [
                    'key' => 'php_cli',
                    'message' => $this->translator->trans('config.php_cli.not_found'),
                ],
                Response::HTTP_BAD_REQUEST);
        }

        $vWeb = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $vCli = vsprintf('%s.%s', explode('.', $process->getOutput()));

        if (version_compare($vWeb, $vCli, '<>')) {
            return new JsonResponse(
                [
                    'key' => 'php_cli',
                    'message' => $this->translator->trans(
                        'config.php_cli.incompatible',
                        ['cli' => $vCli, 'web' => $vWeb]
                    ),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return null;
    }
}
