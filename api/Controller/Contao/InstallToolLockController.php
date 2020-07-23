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

use Composer\Semver\VersionParser;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ContaoConsole;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao/install-tool/lock", methods={"GET", "PUT", "DELETE"})
 */
class InstallToolLockController
{
    /**
     * @var ContaoConsole
     */
    private $console;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $lockFile;

    public function __construct(ContaoConsole $console, ApiKernel $kernel, Filesystem $filesystem)
    {
        $this->console = $console;
        $this->filesystem = $filesystem;

        $this->lockFile = $kernel->getProjectDir().'/var/install_lock';
    }

    public function __invoke(Request $request): Response
    {
        try {
            $contaoVersion = $this->console->getVersion();
        } catch (\RuntimeException $e) {
            $contaoVersion = null;
        }

        if (null === $contaoVersion
            || version_compare((new VersionParser())->normalize($contaoVersion), '4.4.9', '<')
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support locking the install tool.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->getLockStatus();

            case 'PUT':
                return $this->lockInstallTool();

            case 'DELETE':
                return $this->unlockInstallTool();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function getLockStatus(): Response
    {
        return new JsonResponse(
            [
                'locked' => $this->isLocked(),
            ]
        );
    }

    private function lockInstallTool(): Response
    {
        $this->filesystem->dumpFile($this->lockFile, 3);

        return $this->getLockStatus();
    }

    private function unlockInstallTool(): Response
    {
        $this->filesystem->remove($this->lockFile);

        return $this->getLockStatus();
    }

    private function isLocked()
    {
        return $this->filesystem->exists($this->lockFile) && @file_get_contents($this->lockFile) >= 3;
    }
}
