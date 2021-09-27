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
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var UserConfig
     */
    private $config;

    /**
     * Constructor.
     */
    public function __construct(UserConfig $config)
    {
        $this->config = $config;
    }

    public function supports(Request $request): bool
    {
        if ($request->headers->has('Contao-Manager-Auth')) {
            return true;
        }

        $authentication = $this->getAuthenticationHeader($request);

        return \is_string($authentication) && 0 === stripos($authentication, 'bearer ');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    public function getCredentials(Request $request)
    {
        if ($request->headers->has('Contao-Manager-Auth')) {
            return $request->headers->get('Contao-Manager-Auth');
        }

        $authentication = $this->getAuthenticationHeader($request);

        if (\is_string($authentication) && 0 === stripos($authentication, 'bearer ')) {
            return substr($authentication, 7);
        }

        return '';
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $token = $this->config->findToken($credentials);

        if (null === $token) {
            return null;
        }

        return $userProvider->loadUserByUsername($token['username']);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        $token->setAttribute('authenticator', static::class);

        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * Gets the authentication header from request or HTTP headers.
     */
    private function getAuthenticationHeader(Request $request): ?string
    {
        if ($request->server->has('HTTP_AUTHORIZATION')) {
            return $request->server->get('HTTP_AUTHORIZATION');
        }

        if ($request->server->has('REDIRECT_HTTP_AUTHORIZATION')) {
            return $request->server->get('REDIRECT_HTTP_AUTHORIZATION');
        }

        if (\function_exists('getallheaders')) {
            $headers = getallheaders();

            if (isset($headers['authorization'])) {
                return $headers['authorization'];
            }
        }

        return null;
    }
}
