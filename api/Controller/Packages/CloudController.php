<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Json\JsonFile;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Task\TaskManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CloudController
{
    public function __construct(
        private readonly Environment $environment,
        private readonly TaskManager $taskManager,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route(path: '/packages/cloud', methods: ['GET', 'PUT'])]
    #[IsGranted('ROLE_READ')]
    public function getCloudData(): Response
    {
        return new JsonResponse([
            'composerJson' => $this->environment->getComposerJson(),
            'composerLock' => $this->environment->getComposerLock(),
            'platform' => $this->environment->getPlatformPackages(),
            'localPackages' => $this->environment->getLocalPackages(),
        ]);
    }

    #[Route(path: '/packages/cloud', methods: ['PUT'])]
    #[IsGranted('ROLE_INSTALL')]
    public function writeAndInstall(Request $request): Response
    {
        if ($this->taskManager->hasTask()) {
            throw new BadRequestHttpException('A task is already active');
        }

        $lock = $request->request->all('composerLock');

        if (null === $lock) {
            return new Response('composerLock is missing', Response::HTTP_BAD_REQUEST);
        }

        try {
            $lockFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
            $lockFile->write($lock);
            $lockContent = $lockFile->read(); // Validates the JSON

            if (null !== ($json = $request->request->all('composerJson'))) {
                $jsonFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
                $jsonFile->write($json);
                $jsonFile->validateSchema(JsonFile::LAX_SCHEMA);
                $this->environment->getComposerJsonFile()->write($jsonFile->read());
            }

            // Only write after composer.json was validated
            $this->environment->getComposerLockFile()->write($lockContent);
        } catch (\Throwable $throwable) {
            return ApiProblemResponse::createFromException($throwable);
        }

        return new JsonResponse($this->taskManager->createTask('composer/install', []));
    }
}
