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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
    private string $tokenId;

    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly UserConfig $config,
    ) {
    }

    public function supports(Request $request): bool
    {
        if ($request->headers->has('Contao-Manager-Auth')) {
            return true;
        }

        $authentication = $this->getAuthenticationHeader($request);

        return \is_string($authentication) && 0 === stripos($authentication, 'bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $token = $this->config->findToken($this->getToken($request));

        if (null === $token || 'one-time' === ($token['grant_type'] ?? null)) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        $this->tokenId = $token['id'];

        $userBadge = new UserBadge(
            $token['username'],
            $this->userProvider->loadUserByIdentifier(...),
            ['roles' => 'ROLE_'.strtoupper($token['scope'])],
        );

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response|null
    {
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response|null
    {
        $token->setAttribute('authenticator', static::class);
        $token->setAttribute('token_id', $this->tokenId);

        return null;
    }

    /**
     * Gets the authentication header from request or HTTP headers.
     */
    private function getAuthenticationHeader(Request $request): string|null
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

    private function getToken(Request $request): string
    {
        if ($request->headers->has('Contao-Manager-Auth')) {
            return $request->headers->get('Contao-Manager-Auth');
        }

        $authentication = $this->getAuthenticationHeader($request);

        if (\is_string($authentication) && 0 === stripos($authentication, 'bearer ')) {
            return substr($authentication, 7);
        }

        throw new AuthenticationCredentialsNotFoundException();
    }
}
