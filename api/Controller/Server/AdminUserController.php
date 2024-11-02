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
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/server/admin-user', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_INSTALL')]
class AdminUserController
{
    public function __construct(private readonly ContaoConsole $contaoConsole)
    {
    }

    public function __invoke(Request $request, ServerInfo $serverInfo): Response
    {
        if (!$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE),
            );
        }

        $commands = $this->contaoConsole->getCommandList();

        if (
            !isset($commands['contao:user:list']['options'], $commands['contao:user:create']['options'])
            || !\in_array('format', $commands['contao:user:list']['options'], true)
            || !\in_array('column', $commands['contao:user:list']['options'], true)
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao console does not support the necessary contao:user:list and/or contao:user:create commands/options.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED),
            );
        }

        if ($request->isMethod('POST')) {
            if ($this->hasAdminUser()) {
                return new ApiProblemResponse(
                    (new ApiProblem('An admin user already exists.'))
                        ->setStatus(Response::HTTP_METHOD_NOT_ALLOWED),
                );
            }

            try {
                $this->contaoConsole->createBackendUser(
                    [
                        'username' => $request->request->get('username'),
                        'name' => $request->request->get('name'),
                        'email' => $request->request->get('email'),
                        'language' => $request->getPreferredLanguage(),
                    ],
                    $request->request->get('password'),
                );

                return $this->getUserResponse(Response::HTTP_CREATED);
            } catch (ProcessFailedException $exception) {
                $problem = new ApiProblem('Unable to create back end account.');
                $problem->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
                $problem['debug'] = $exception->getProcess()->getOutput().$exception->getProcess()->getErrorOutput();

                return new ApiProblemResponse($problem);
            }
        }

        return $this->getUserResponse();
    }

    private function getUserResponse(int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(
            [
                'hasUser' => $this->hasAdminUser(true),
            ],
            $status,
        );
    }

    private function hasAdminUser(bool $throw = false): bool
    {
        $users = $this->contaoConsole->getUsers($throw);

        foreach ($users as $user) {
            if ($user['admin']) {
                return true;
            }
        }

        return false;
    }
}
