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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PasswordlessAuthenticator extends AbstractBrowserAuthenticator
{
    private string $tokenId;

    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly UserConfig $userConfig,
        JwtManager $jwtManager,
        Filesystem $filesystem,
        ApiKernel $kernel,
    ) {
        parent::__construct($jwtManager, $this->userConfig, $filesystem, $kernel);
    }

    public function supports(Request $request): bool
    {
        if (!parent::supports($request) || !$request->request->has('token')) {
            return false;
        }

        $token = $this->userConfig->findToken($request->request->get('token'));

        return $token && 'one-time' === ($token['grant_type'] ?? null);
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $token = $this->userConfig->findToken($request->request->get('token'));

        if (null === $token || 'one-time' !== ($token['grant_type'] ?? null)) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        $this->tokenId = $token['id'];

        $userBadge = new UserBadge(
            $token['username'],
            $this->userProvider->loadUserByIdentifier(...),
            ['scope' => $token['scope']],
        );

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $this->userConfig->deleteToken($this->tokenId);

        return parent::onAuthenticationSuccess($request, $token, $firewallName);
    }
}
