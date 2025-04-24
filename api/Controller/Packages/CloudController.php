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
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Terminal42\ComposerLockValidator\Validator;

class CloudController extends AbstractController
{
    public function __construct(
        private readonly Environment $environment,
        private readonly TaskManager $taskManager,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/packages/cloud', methods: ['GET'])]
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
    #[IsGranted('ROLE_UPDATE')]
    public function writeAndInstall(Request $request): Response
    {
        if ($this->taskManager->hasTask()) {
            throw new BadRequestHttpException('A task is already active');
        }

        $valid = false;
        $lock = $request->request->all('composerLock');

        if ([] === $lock) {
            return new Response('composerLock is missing', Response::HTTP_BAD_REQUEST);
        }

        try {
            $lockFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
            $lockFile->write($lock);
            $lockContent = $lockFile->read(); // Validates the JSON
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerLock for /api/packages/cloud.', ['composerLock' => $lock]);

            return ApiProblemResponse::createFromException($throwable)->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        try {
            if ($request->request->has('composerJson') && [] !== ($json = $request->request->all('composerJson'))) {
                $jsonFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
                $jsonFile->write($json);
                $jsonFile->validateSchema(JsonFile::LAX_SCHEMA);

                if (!$this->isGranted('ROLE_INSTALL') && $jsonFile->read() !== $this->environment->getComposerJson()) {
                    throw $this->createAccessDeniedException('No permission to change the composer.json');
                }

                Validator::createFromComposerJson($jsonFile->getPath())->validate($lockContent);
                $valid = true;

                $this->environment->getComposerJsonFile()->write($jsonFile->read());
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerJson for /api/packages/cloud.', ['composerJson' => $json ?? null, 'composerLock' => $lock]);

            return ApiProblemResponse::createFromException($throwable)->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // If a composer.json was submitted as well, the composer.lock has already been
        // validated at this point.
        if (!$valid) {
            try {
                Validator::createFromComposer($this->environment->getComposer(true))->validate($lockContent);
            } catch (\Throwable $throwable) {
                $this->logger->error('Invalid composerJson for /api/packages/cloud.', ['composerJson' => $json ?? null, 'composerLock' => $lock]);

                return ApiProblemResponse::createFromException($throwable)->setStatusCode(Response::HTTP_BAD_REQUEST);
            }
        }

        // Only write after composer.json was validated
        $this->environment->getComposerLockFile()->write($lockContent);

        return new JsonResponse($this->taskManager->createTask('composer/install', []));
    }
}
