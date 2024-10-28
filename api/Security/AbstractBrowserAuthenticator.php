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
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;

abstract class AbstractBrowserAuthenticator extends AbstractAuthenticator
{
    private const LOCK_FILE = 'login.lock';

    public function __construct(
        private readonly JwtManager $jwtManager,
        private readonly Filesystem $filesystem,
        private readonly ApiKernel $kernel
    ) {
    }

    public function supports(Request $request): bool
    {
        // Manager login is locked
        if (self::isLocked($this->kernel->getConfigDir())) {
            return false;
        }

        return '/api/session' === $request->getPathInfo() && $request->isMethod(Request::METHOD_POST);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        // Reset lock counter
        $this->filesystem->dumpFile($this->kernel->getConfigDir().\DIRECTORY_SEPARATOR.self::LOCK_FILE, '0');

        $token->setAttribute('authenticator', static::class);

        $response = new JsonResponse(['username' => $token->getUserIdentifier()]);

        $this->jwtManager->addToken($request, $response, $token->getUserIdentifier());

        return $response;
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

    public static function isLocked(string $configDir): bool
    {
        return self::getLockCount($configDir) >= 3;
    }

    private static function getLockCount(string $configDir): int
    {
        return (int) @file_get_contents($configDir.\DIRECTORY_SEPARATOR.self::LOCK_FILE);
    }
}
