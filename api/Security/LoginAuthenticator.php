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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;

class LoginAuthenticator extends AbstractBrowserAuthenticator implements PasswordAuthenticatedInterface
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory, JwtManager $jwtManager, Filesystem $filesystem, ApiKernel $kernel)
    {
        parent::__construct($jwtManager, $filesystem, $kernel);

        $this->encoderFactory = $encoderFactory;
    }

    public function supports(Request $request): bool
    {
        return parent::supports($request)
            && $request->request->has('username')
            && $request->request->has('password');
    }

    public function getCredentials(Request $request)
    {
        return $request->request->all();
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $user = $userProvider->loadUserByUsername($credentials['username']);

        if ($userProvider instanceof PasswordUpgraderInterface && null === $user->getPassword()) {
            $encoder = $this->encoderFactory->getEncoder($user);
            $userProvider->upgradePassword($user, $encoder->encodePassword($credentials['password'], null));
            $user = $userProvider->loadUserByUsername($user->getUsername());
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->encoderFactory->getEncoder($user)->isPasswordValid(
            $user->getPassword(),
            $credentials['password'],
            null
        );
    }

    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }
}
