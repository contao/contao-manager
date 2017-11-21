<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Security\JwtManager;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SessionController extends Controller
{
    /**
     * @var UserConfig
     */
    private $config;

    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * Constructor.
     *
     * @param UserConfig                   $config
     * @param JwtManager                   $jwtManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        UserConfig $config,
        JwtManager $jwtManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->config = $config;
        $this->jwtManager = $jwtManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Handles the controller action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request)
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
     *
     * @return Response
     */
    private function getStatus()
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(['username' => (string) $this->getUser()]);
        }

        if (0 === $this->config->countUsers()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    /**
     * Logs the user in from request data. If no user exist, the first user is created from this data.
     *
     * @param Request $request
     *
     * @return Response
     */
    private function handleLogin(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
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
     *
     * @return Response
     */
    private function handleLogout(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $response = new Response('', Response::HTTP_NO_CONTENT);

        $this->jwtManager->removeToken($request, $response);

        return $response;
    }
}
