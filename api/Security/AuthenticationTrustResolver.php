<?php

declare(strict_types=1);

namespace Contao\ManagerApi\Security;

use Contao\ManagerApi\Config\UserConfig;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationTrustResolver implements AuthenticationTrustResolverInterface
{
    public function __construct(
        private readonly AuthenticationTrustResolverInterface $inner,
        private readonly UserConfig $config,
    ) {
    }

    public function isAuthenticated(TokenInterface|null $token = null): bool
    {
        return $this->inner->isAuthenticated($token);
    }

    public function isRememberMe(TokenInterface|null $token = null): bool
    {
        return $this->inner->isRememberMe($token);
    }

    public function isFullFledged(TokenInterface|null $token = null): bool
    {
        if (!$this->inner->isFullFledged($token) || !$token?->getUserIdentifier()) {
            return false;
        }

        return User::scopeFromRoles($token->getRoleNames()) === User::scopeFromRoles($this->config->getUser($token->getUserIdentifier())?->getRoles());
    }
}
