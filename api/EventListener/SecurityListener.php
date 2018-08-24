<?php

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
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityListener
{
    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * Constructor.
     *
     * @param JwtManager                    $jwtManager
     * @param TokenStorageInterface         $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        JwtManager $jwtManager,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->jwtManager = $jwtManager;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Adds and/or renews the JWT token on kernel.response event.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest() || $this->jwtManager->hasResponseToken($event->getResponse())) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null !== $token
            && $token->hasAttribute('authenticator')
            && $token->getAttribute('authenticator') === JwtAuthenticator::class
            && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
        ) {
            $this->jwtManager->addToken($event->getRequest(), $event->getResponse(), $token->getUsername());
        } else {
            $this->jwtManager->removeToken($event->getRequest(), $event->getResponse());
        }
    }
}
