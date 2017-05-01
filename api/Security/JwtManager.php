<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Config\ManagerConfig;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class JwtManager
{
    const COOKIE_AUTH = 'contao_manager_auth';
    const COOKIE_XSRF = 'contao_manager_xsrf';

    /**
     * @var ManagerConfig
     */
    private $config;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * Constructor.
     *
     * @param ManagerConfig $config
     */
    public function __construct(ManagerConfig $config)
    {
        $this->config = $config;
        $this->tokenGenerator = new UriSafeTokenGenerator();
    }

    /**
     * Gets payload data from JWT token cookie in the request.
     *
     * @param Request $request
     *
     * @return object|null
     */
    public function getPayload(Request $request)
    {
        if (!$request->cookies->has(self::COOKIE_AUTH)) {
            return null;
        }

        try {
            return JWT::decode(
                $request->cookies->get(self::COOKIE_AUTH),
                $this->config->getSecret(),
                ['HS256']
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if the response has a JWT cookie.
     *
     * @param Response $response
     *
     * @return bool
     */
    public function hasToken(Response $response)
    {
        return $this->hasCookie($response, self::COOKIE_AUTH);
    }

    /**
     * Adds JWT auth and XSRF cookies to the given response.
     *
     * @param Request  $request
     * @param Response $response
     * @param string   $username
     */
    public function addToken(Request $request, Response $response, $username)
    {
        $payload = [
            'iat' => time(),
            'exp' => strtotime('+10 minutes'),
            'username' => $username,
            'xsrf' => $this->getXsrfToken($request, $response),
        ];

        // TODO optimize cookie configuration
        $response->headers->setCookie(
            new Cookie(
                self::COOKIE_AUTH,
                JWT::encode($payload, $this->config->getSecret(), 'HS256')
            )
        );
    }

    /**
     * Clears the JWT cookie in the response.
     *
     * @param Response $response
     */
    public function removeToken(Response $response)
    {
        $response->headers->clearCookie(self::COOKIE_AUTH);
    }

    /**
     * Retrieves the XSRF token from the request or creates one and adds it to the response.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return string
     */
    private function getXsrfToken(Request $request, Response $response)
    {
        if ($request->cookies->has(self::COOKIE_XSRF)) {
            return $request->cookies->get(self::COOKIE_XSRF);
        }

        $token = $this->tokenGenerator->generateToken();

        // TODO optimize cookie configuration
        $response->headers->setCookie(
            new Cookie(
                self::COOKIE_XSRF,
                $token,
                0,
                '/',
                null,
                false,
                false
            )
        );

        return $token;
    }

    /**
     * Returns whether the response has a cookie with that name.
     *
     * @param Response $response
     * @param string   $cookieName
     *
     * @return bool
     */
    private function hasCookie(Response $response, $cookieName)
    {
        /** @var Cookie[] $cookies */
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return true;
            }
        }

        return false;
    }
}
