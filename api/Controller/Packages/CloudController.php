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
use Contao\ManagerApi\Task\TaskManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

        $backupCreated = false;
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

            throw new BadRequestHttpException($throwable->getMessage(), $throwable);
        }

        try {
            if ($request->request->has('composerJson') && [] !== ($json = $request->request->all('composerJson'))) {
                $jsonFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
                $jsonFile->write($json);
                $jsonFile->validateSchema(JsonFile::LAX_SCHEMA);

                if (!$this->isGranted('ROLE_INSTALL') && $jsonFile->read() !== $this->environment->getComposerJsonFile()->read()) {
                    throw $this->createAccessDeniedException('No permission to change the composer.json');
                }

                if (!$this->environment->createBackup()) {
                    $this->logger->error('Unable to create backup of composer files.', ['composerJson' => $json ?? null, 'composerLock' => $lock]);

                    throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Unable to create backup of composer files.');
                }

                $backupCreated = true;
                $this->environment->getComposerJsonFile()->write($jsonFile->read());
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerJson for /api/packages/cloud: '.$throwable->getMessage(), ['composerJson' => $json ?? null, 'composerLock' => $lock]);

            if ($throwable instanceof HttpExceptionInterface) {
                throw $throwable;
            }

            throw new BadRequestHttpException($throwable->getMessage(), $throwable);
        }

        try {
            Validator::createFromComposer($this->environment->getComposer(true))
                ->validate($lockContent, $this->environment->getComposerLock())
            ;
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerLock for /api/packages/cloud: '.$throwable->getMessage(), ['composerJson' => $json ?? null, 'composerLock' => $lock]);

            if ($backupCreated) {
                $this->environment->restoreBackup();
            }

            throw new BadRequestHttpException($throwable->getMessage(), $throwable);
        }

        // Only write after composer.json was validated
        $this->environment->getComposerLockFile()->write($lockContent);

        return new JsonResponse($this->taskManager->createTask('composer/install', []));
    }
}
