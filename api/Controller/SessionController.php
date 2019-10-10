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
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
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
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(
        UserConfig $config,
        Security $security,
        JwtManager $jwtManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->config = $config;
        $this->security = $security;
        $this->jwtManager = $jwtManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getStatus();

            case 'POST':
                return $this->handleLogin($request);

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
            return new JsonResponse(['username' => (string) $this->security->getUser()]);
        }

        if (0 === $this->config->countUsers()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    /**
     * Logs the user in from request data. If no user exist, the first user is created from this data.
     */
    private function handleLogin(Request $request): Response
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new ApiProblemResponse(
                (new ApiProblem('User is already logged in'))->setStatus(Response::HTTP_BAD_REQUEST)
            );
        }

        $username = $request->request->get('username');
        $password = $request->request->get('password');

        if (0 === $this->config->countUsers()) {
            $this->config->addUser(
                $this->config->createUser($username, $password)
            );
        }

        if (!$this->config->hasUser($username)
            || !$this->passwordEncoder->isPasswordValid($this->config->getUser($username), $password)
        ) {
            return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
        }

        $response = new JsonResponse(['username' => $username]);

        $this->jwtManager->addToken($request, $response, $username);

        return $response;
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
