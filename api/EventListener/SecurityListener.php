<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\EventListener;

use Contao\ManagerApi\Security\JwtAuthenticator;
use Contao\ManagerApi\Security\JwtManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsEventListener]
class SecurityListener
{
    public function __construct(private readonly JwtManager $jwtManager, private readonly TokenStorageInterface $tokenStorage, private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    /**
     * Adds and/or renews the JWT token on kernel.response event.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || $this->jwtManager->hasResponseToken($event->getResponse())) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (
            null !== $token
            && $token->hasAttribute('authenticator')
            && JwtAuthenticator::class === $token->getAttribute('authenticator')
            && $this->authorizationChecker->isGranted('ROLE_USER')
        ) {
            $this->jwtManager->addToken($event->getRequest(), $event->getResponse(), $token->getUserIdentifier());
        } else {
            $this->jwtManager->removeToken($event->getRequest(), $event->getResponse());
        }
    }
}
