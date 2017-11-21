<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\System;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PhpCliController extends Controller
{
    /**
     * Gets response about PHP command line version and issues.
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

        return new JsonResponse($this->runIntegrityChecks());
    }

    private function runIntegrityChecks()
    {
        $process = $this->get('contao_manager.process.console_factory')->createManagerConsoleProcess(
            [
                'integrity-check',
                '--format=json',
            ]
        );

        $process->run();

        return json_decode($process->getOutput(), true);
    }
}
