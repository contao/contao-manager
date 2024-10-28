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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PasswordlessAuthenticator extends AbstractBrowserAuthenticator
{
    /**
     * @var string
     */
    private $tokenId;

    public function __construct(private readonly UserConfig $config, JwtManager $jwtManager, Filesystem $filesystem, ApiKernel $kernel)
    {
        parent::__construct($jwtManager, $filesystem, $kernel);
    }

    public function supports(Request $request): bool
    {
        return parent::supports($request) && $request->request->has('token');
    }

    public function getCredentials(Request $request)
    {
        return $request->request->get('token');
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $token = $this->config->findToken($credentials);

        if (null === $token || 'one-time' !== ($token['grant_type'] ?? null)) {
            return null;
        }

        $this->tokenId = $token['id'];

        return $userProvider->loadUserByUsername($token['username']);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): Response
    {
        $this->config->deleteToken($this->tokenId);

        return parent::onAuthenticationSuccess($request, $token, $providerKey);
    }
}
