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

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;

class LoginAuthenticator extends AbstractGuardAuthenticator implements PasswordAuthenticatedInterface
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $lockFile;

    public function __construct(EncoderFactoryInterface $encoderFactory, JwtManager $jwtManager, Filesystem $filesystem, string $lockFile)
    {
        $this->encoderFactory = $encoderFactory;
        $this->jwtManager = $jwtManager;
        $this->filesystem = $filesystem;
        $this->lockFile = $lockFile;
    }

    public function supports(Request $request): bool
    {
        // Manager login is locked
        if (((int) @file_get_contents($this->lockFile)) >= 3) {
            return false;
        }

        return '/api/session' === $request->getPathInfo()
            && $request->isMethod(Request::METHOD_POST)
            && $request->request->has('username')
            && $request->request->has('password');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    public function getCredentials(Request $request)
    {
        return $request->request->all();
    }

    public function getUser($credentials, UserProviderInterface $userProvider): User
    {
        $user = $userProvider->loadUserByUsername($credentials['username']);

        if (null === $user->getPassword() && $userProvider instanceof PasswordUpgraderInterface) {
            $encoder = $this->encoderFactory->getEncoder($user);
            $userProvider->upgradePassword($user, $encoder->encodePassword($credentials['password'], null));
            $user = $userProvider->loadUserByUsername($user->getUsername());
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->isPasswordValid($user->getPassword(), $credentials['password'], null);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Increase lock counter
        $this->filesystem->dumpFile($this->lockFile, (string) (((int) @file_get_contents($this->lockFile)) + 1));

        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): Response
    {
        // Reset lock counter
        $this->filesystem->dumpFile($this->lockFile, '0');

        $token->setAttribute('authenticator', static::class);

        $response = new JsonResponse(['username' => $token->getUsername()]);

        $this->jwtManager->addToken($request, $response, $token->getUsername());

        return $response;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }
}
