<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ContaoApi;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DatabaseController
{
    // double escaping is necessary for JavaScript validation
    private const URL_PATTERN = '^([^:]+)://(([^:@]+)(:([^@]+))?@)?([^:\/]+(:[0-9]+)?)/([^?]+)(\\?.+)?$';

    public function __construct(
        private readonly ContaoApi $contaoApi,
        private readonly ContaoConsole $contaoConsole,
    ) {
    }

    #[Route(path: '/server/database', methods: ['GET'])]
    #[IsGranted('ROLE_READ')]
    public function handle(Request $request, ServerInfo $serverInfo): Response
    {
        if (!$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE),
            );
        }

        $commands = $this->contaoConsole->getCommandList();
        $features = $this->contaoApi->getFeatures();

        if (
            !isset($commands['contao:migrate']['options'], $features['contao/manager-bundle']['dot-env'])
            || !\in_array('format', $commands['contao:migrate']['options'], true)
            || !\in_array('dry-run', $commands['contao:migrate']['options'], true)
            || !\in_array('hash', $commands['contao:migrate']['options'], true)
            || !\in_array('DATABASE_URL', $features['contao/manager-bundle']['dot-env'], true)
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao console does not support the necessary contao:migrate command/arguments or CLI API features.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED),
            );
        }

        if ($request->isMethod('POST')) {
            $url = $request->request->get('url');

            if (empty($url) || !preg_match('{'.self::URL_PATTERN.'}i', $url)) {
                return new ApiProblemResponse(
                    (new ApiProblem('Invalid URL'))
                        ->setStatus(Response::HTTP_BAD_REQUEST),
                );
            }

            $this->contaoApi->runCommand(['dot-env:set', 'DATABASE_URL', $url]);
        } else {
            $url = $this->contaoApi->runCommand(['dot-env:get', 'DATABASE_URL']);
        }

        return new JsonResponse([
            'url' => $url,
            'pattern' => self::URL_PATTERN,
            'status' => $this->contaoConsole->checkDatabaseMigrations(),
        ]);
    }

    #[Route(path: '/server/database', methods: ['POST'])]
    #[IsGranted('ROLE_INSTALL')]
    public function write(Request $request, ServerInfo $serverInfo): Response
    {
        return $this->handle($request, $serverInfo);
    }
}
