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

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\UserConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JwtManager
{
    public const COOKIE_AUTH = 'contao_manager_auth';

    public function __construct(private readonly UserConfig $users)
    {
    }

    /**
     * Gets payload data from JWT token cookie in the request.
     */
    public function getPayload(Request $request): \stdClass|null
    {
        if (!$request->cookies->has(self::COOKIE_AUTH)) {
            return null;
        }

        try {
            return JWT::decode(
                $request->cookies->get(self::COOKIE_AUTH),
                new Key($this->users->getSecret(), 'HS256'),
            );
        } catch (\Exception) {
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
        return $this->hasCookie($response);
    }

    /**
     * Adds JWT auth cookies to the given response.
     */
    public function addToken(Request $request, Response $response, TokenInterface $token): void
    {
        $payload = [
            'iat' => time(),
            'exp' => strtotime('+30 minutes'),
            'username' => $token->getUserIdentifier(),
            'roles' => $token->getRoleNames(),
        ];

        $response->headers->setCookie(
            $this->createCookie(
                JWT::encode($payload, $this->users->getSecret(), 'HS256'),
                $request,
            ),
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
            ApiKernel::isPhar() ? $request->getBaseUrl().'/' : '/',
            null,
            $request->isSecure(),
        );
    }

    /**
     * Returns whether the response has a cookie with that name.
     */
    private function hasCookie(Response $response): bool
    {
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if (self::COOKIE_AUTH === $cookie->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates a cookie configured for Contao Manager.
     */
    private function createCookie(string $value, Request $request): Cookie
    {
        return Cookie::create(
            self::COOKIE_AUTH,
            $value,
            0,
            ApiKernel::isPhar() ? $request->getBaseUrl().'/' : '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_STRICT,
        );
    }
}
