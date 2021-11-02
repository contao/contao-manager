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

use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Security\JwtManager;
use Contao\ManagerApi\Security\TokenAuthenticator;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/session", methods={"GET", "POST", "DELETE"})
 */
class SessionController
{
    /**
     * @var UserConfig
     */
    private $config;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var string
     */
    private $lockFile;

    public function __construct(UserConfig $config, Security $security, JwtManager $jwtManager, string $lockFile)
    {
        $this->config = $config;
        $this->security = $security;
        $this->jwtManager = $jwtManager;
        $this->lockFile = $lockFile;
    }

    public function __invoke(Request $request): Response
    {
        if (((int) @file_get_contents($this->lockFile)) >= 3) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->getStatus();

            case 'POST':
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
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $token = $this->security->getToken();

            if (null !== $token
                && TokenAuthenticator::class === $token->getAttribute('authenticator')
                && null !== ($payload = $this->config->getToken($token->getAttribute('token_id')))
            ) {
                return new JsonResponse($payload);
            }

            return new JsonResponse(['username' => (string) $this->security->getUser()]);
        }

        if (0 === $this->config->countUsers()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    /**
     * Logs the user out by removing cookies from the browser.
     */
    private function handleLogout(Request $request): Response
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new ApiProblemResponse(
                (new ApiProblem('User is not logged in'))->setStatus(Response::HTTP_UNAUTHORIZED)
            );
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);

        $this->jwtManager->removeToken($request, $response);

        return $response;
    }
}
