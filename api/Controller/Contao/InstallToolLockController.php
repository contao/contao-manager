<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Contao;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\MultiConstraint;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ContaoConsole;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/contao/install-tool/lock', methods: ['GET', 'PUT', 'DELETE'])]
class InstallToolLockController
{
    private readonly string $lockFile;

    public function __construct(
        private readonly ContaoConsole $console,
        ApiKernel $kernel,
        private readonly Filesystem $filesystem,
    ) {
        $this->lockFile = $kernel->getProjectDir().'/var/install_lock';
    }

    public function __invoke(Request $request): Response
    {
        try {
            $contaoVersion = $this->console->getVersion();
        } catch (\RuntimeException) {
            $contaoVersion = null;
        }

        if (
            null === $contaoVersion
            || (new MultiConstraint(
                [
                    new Constraint('<', '4.4.9'),
                    new Constraint('>', '4.13.9999'), // 5.0 including dev versions
                ],
                false,
            ))->matches(new Constraint('=', $contaoVersion))
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support locking the install tool.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED),
            );
        }

        return match ($request->getMethod()) {
            'GET' => $this->getLockStatus(),
            'PUT' => $this->lockInstallTool(),
            'DELETE' => $this->unlockInstallTool(),
            default => new Response(null, Response::HTTP_METHOD_NOT_ALLOWED),
        };
    }

    private function getLockStatus(): Response
    {
        return new JsonResponse(
            [
                'locked' => $this->isLocked(),
            ],
        );
    }

    private function lockInstallTool(): Response
    {
        $this->filesystem->dumpFile($this->lockFile, '3');

        return $this->getLockStatus();
    }

    private function unlockInstallTool(): Response
    {
        $this->filesystem->remove($this->lockFile);

        return $this->getLockStatus();
    }

    private function isLocked(): bool
    {
        return $this->filesystem->exists($this->lockFile) && @file_get_contents($this->lockFile) >= 3;
    }
}
