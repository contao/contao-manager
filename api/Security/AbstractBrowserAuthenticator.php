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
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

abstract class AbstractBrowserAuthenticator extends AbstractGuardAuthenticator
{
    private const LOCK_FILE = 'login.lock';

    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ApiKernel
     */
    private $kernel;

    public function __construct(JwtManager $jwtManager, Filesystem $filesystem, ApiKernel $kernel)
    {
        $this->jwtManager = $jwtManager;
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
    }

    public function supports(Request $request): bool
    {
        // Manager login is locked
        if (self::getLockCount($this->kernel->getConfigDir()) >= 3) {
            return false;
        }

        return '/api/session' === $request->getPathInfo() && $request->isMethod(Request::METHOD_POST);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Increase lock counter
        $this->filesystem->dumpFile(
            $this->kernel->getConfigDir().\DIRECTORY_SEPARATOR.self::LOCK_FILE,
            (string) (self::getLockCount($this->kernel->getConfigDir()) + 1)
        );

        return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_UNAUTHORIZED));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): Response
    {
        // Reset lock counter
        $this->filesystem->dumpFile($this->kernel->getConfigDir().\DIRECTORY_SEPARATOR.self::LOCK_FILE, '0');

        $token->setAttribute('authenticator', static::class);

        $response = new JsonResponse(['username' => $token->getUsername()]);

        $this->jwtManager->addToken($request, $response, $token->getUsername());

        return $response;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public static function isLocked(string $configDir): bool
    {
        return self::getLockCount($configDir) >= 3;
    }

    private static function getLockCount(string $configDir): int
    {
        return (int) @file_get_contents($configDir.\DIRECTORY_SEPARATOR.self::LOCK_FILE);
    }
}
