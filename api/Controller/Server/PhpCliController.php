<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\System\ServerInfo;
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
    public function __invoke(ManagerConfig $managerConfig, ServerInfo $serverInfo, ConsoleProcessFactory $processFactory)
    {
        if (!$managerConfig->has('server') || !$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        return new JsonResponse($this->runIntegrityChecks($processFactory));
    }

    private function runIntegrityChecks(ConsoleProcessFactory $processFactory)
    {
        $process = $processFactory->createManagerConsoleProcess(
            [
                'integrity-check',
                '--format=json',
            ]
        );

        $process->run();

        return json_decode($process->getOutput(), true);
    }
}
