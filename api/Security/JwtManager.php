<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Config\UserConfig;
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
     * @var UserConfig
     */
    private $users;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * Constructor.
     *
     * @param UserConfig $users
     */
    public function __construct(UserConfig $users)
    {
        $this->users = $users;
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
                $this->users->getSecret(),
                ['HS256']
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if the request has a JWT cookie.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function hasRequestToken(Request $request)
    {
        return $request->cookies->has(self::COOKIE_AUTH);
    }

    /**
     * Checks if the response has a JWT cookie.
     *
     * @param Response $response
     *
     * @return bool
     */
    public function hasResponseToken(Response $response)
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

        $response->headers->setCookie(
            $this->createCookie(
                self::COOKIE_AUTH,
                JWT::encode($payload, $this->users->getSecret(), 'HS256'),
                $request,
                true
            )
        );
    }

    /**
     * Clears the JWT cookie in the response.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function removeToken(Request $request, Response $response)
    {
        $response->headers->clearCookie(
            self::COOKIE_AUTH,
            \Phar::running(false) ? $request->getBaseUrl().'/' : '/',
            null,
            $request->isSecure(),
            true
        );
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

        $response->headers->setCookie(
            $this->createCookie(self::COOKIE_XSRF, $token, $request, false)
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

    /**
     * Creates a cookie configured for Contao Manager.
     *
     * @param string  $name
     * @param string  $value
     * @param Request $request
     * @param bool    $httpOnly
     *
     * @return Cookie
     */
    private function createCookie($name, $value, Request $request, $httpOnly)
    {
        return new Cookie(
            $name,
            $value,
            0,
            \Phar::running(false) ? $request->getBaseUrl().'/' : '/',
            null,
            $request->isSecure(),
            $httpOnly,
            false,
            Cookie::SAMESITE_STRICT
        );
    }
}
