<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Security\JwtManager;
use Contao\ManagerApi\Security\LoginAuthenticator;
use Contao\ManagerApi\Security\TokenAuthenticator;
use Contao\ManagerApi\Security\User;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/session', methods: ['GET', 'POST', 'DELETE'])]
class SessionController
{
    public function __construct(
        private readonly UserConfig $config,
        private readonly Security $security,
        private readonly JwtManager $jwtManager,
        private readonly ApiKernel $kernel,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getStatus();

            case 'POST':
                if (LoginAuthenticator::isLocked($this->kernel->getConfigDir())) {
                    return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_FORBIDDEN));
                }

                // Login should have been handled by the firewall
                return new Response('Bad Request', Response::HTTP_BAD_REQUEST);

            case 'DELETE':
                return $this->handleLogout($request);
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Returns the login status of the user.
     */
    private function getStatus(): Response
    {
        if ($this->security->isGranted('ROLE_USER')) {
            $token = $this->security->getToken();

            if (
                null !== $token
                && TokenAuthenticator::class === $token->getAttribute('authenticator')
                && null !== ($payload = $this->config->getToken($token->getAttribute('token_id')))
            ) {
                return new JsonResponse($payload);
            }

            $user = $this->config->getUser($token->getUserIdentifier());
            $scope = User::scopeFromRoles($token?->getRoleNames());

            return new JsonResponse([
                'username' => $token?->getUserIdentifier(),
                'scope' => $scope,
                'limited' => $scope !== User::scopeFromRoles($user?->getRoles()),
            ]);
        }

        if (LoginAuthenticator::isLocked($this->kernel->getConfigDir())) {
            return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_FORBIDDEN));
        }

        if (!$this->config->hasUsers()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    /**
     * Logs the user out by removing cookies from the browser.
     */
    private function handleLogout(Request $request): Response
    {
        if (!$this->security->isGranted('ROLE_USER')) {
            return new ApiProblemResponse(
                (new ApiProblem('User is not logged in'))->setStatus(Response::HTTP_UNAUTHORIZED),
            );
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);

        $this->jwtManager->removeToken($request, $response);

        return $response;
    }
}
