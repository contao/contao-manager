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
use Contao\ManagerApi\Security\JwtManager;
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
     * Logs the user in from request data. If no user exist, the first user is created from this data.
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function login(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $username = $request->request->get('username');
        $password = $request->request->get('password');

        if (0 === $this->config->count()) {
            $this->config->addUser(
                $this->config->createUser($username, $password)
            );
        }

        if (!$this->config->hasUser($username)
            || !$this->passwordEncoder->isPasswordValid($this->config->getUser($username), $password)
        ) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $response = new JsonResponse(
            [
                'status' => 'OK',
                'username' => $username,
            ]
        );

        $this->jwtManager->addToken($request, $response, $username);

        return $response;
    }

    /**
     * Logs the user out by removing cookies from the browser.
     *
     * @return Response
     */
    public function logout()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $response = new JsonResponse(
            [
                'status' => 'OK',
            ]
        );

        $this->jwtManager->removeToken($response);

        return $response;
    }
}
