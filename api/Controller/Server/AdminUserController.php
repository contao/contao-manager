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
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/admin-user", methods={"GET", "POST"})
 */
class AdminUserController
{
    /**
     * @var ContaoConsole
     */
    private $contaoConsole;

    public function __construct(ContaoConsole $contaoConsole)
    {
        $this->contaoConsole = $contaoConsole;
    }

    public function __invoke(Request $request, ServerInfo $serverInfo): Response
    {
        if (!$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        $commands = $this->contaoConsole->getCommandList();

        if (
            !isset($commands['contao:user:list']['options'], $commands['contao:user:create']['options'])
            || !\in_array('format', $commands['contao:user:list']['options'], true)
            || !\in_array('column', $commands['contao:user:list']['options'], true)
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Not supported'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        if ($request->isMethod('POST')) {
            if ($this->hasAdminUser()) {
                return new ApiProblemResponse(
                    (new ApiProblem('Admin user already exists'))
                        ->setStatus(Response::HTTP_BAD_REQUEST)
                );
            }

            if (
                !$this->contaoConsole->createAdminUser([
                    'username' => $request->request->get('username'),
                    'name' => $request->request->get('name'),
                    'email' => $request->request->get('email'),
                    'password' => $request->request->get('password'),
                ])
            ) {
                return new ApiProblemResponse(
                    (new ApiProblem('Unable to create user'))
                        ->setStatus(Response::HTTP_BAD_REQUEST)
                );
            }
        }

        return new JsonResponse([
            'hasUser' => $this->hasAdminUser(),
        ]);
    }

    private function hasAdminUser(): bool
    {
        foreach ($this->contaoConsole->getUsers() ?? [] as $user) {
            if ($user['admin']) {
                return true;
            }
        }

        return false;
    }
}
