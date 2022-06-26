<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Config\UserConfig;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtManager
{
    public const COOKIE_AUTH = 'contao_manager_auth';

    /**
     * @var UserConfig
     */
    private $users;

    /**
     * Constructor.
     */
    public function __construct(UserConfig $users)
    {
        $this->users = $users;
    }

    /**
     * Gets payload data from JWT token cookie in the request.
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
     */
    public function hasRequestToken(Request $request): bool
    {
        return $request->cookies->has(self::COOKIE_AUTH);
    }

    /**
     * Checks if the response has a JWT cookie.
     */
    public function hasResponseToken(Response $response): bool
    {
        return $this->hasCookie($response, self::COOKIE_AUTH);
    }

    /**
     * Adds JWT auth cookies to the given response.
     */
    public function addToken(Request $request, Response $response, string $username): void
    {
        $payload = [
            'iat' => time(),
            'exp' => strtotime('+30 minutes'),
            'username' => $username,
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
     */
    public function removeToken(Request $request, Response $response): void
    {
        if (!$request->cookies->has(self::COOKIE_AUTH)) {
            return;
        }

        $response->headers->clearCookie(
            self::COOKIE_AUTH,
            \Phar::running(false) ? $request->getBaseUrl().'/' : '/',
            null,
            $request->isSecure(),
            true
        );
    }

    /**
     * Returns whether the response has a cookie with that name.
     */
    private function hasCookie(Response $response, string $cookieName): bool
    {
        /** @var array<Cookie> $cookies */
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
     */
    private function createCookie(string $name, string $value, Request $request, bool $httpOnly): Cookie
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
