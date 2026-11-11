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
use Contao\ManagerApi\Task\TaskLock;
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
        private readonly TaskLock $taskLock,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/packages/cloud', methods: ['GET'])]
    #[IsGranted('ROLE_READ')]
    public function getCloudData(): Response
    {
        return new JsonResponse([
            'composerJson' => $this->environment->getComposerJsonWithConfig(),
            'composerLock' => $this->environment->getComposerLock(),
            'platform' => $this->environment->getPlatformPackages(),
            'localPackages' => $this->environment->getLocalPackages(),
        ]);
    }

    #[Route(path: '/packages/cloud', methods: ['PUT'])]
    #[IsGranted('ROLE_UPDATE')]
    public function writeAndInstall(Request $request): Response
    {
        return $this->taskLock->synchronized(fn (): Response => $this->install($request));
    }

    private function install(Request $request): Response
    {
        if ($this->taskManager->hasTask()) {
            throw new BadRequestHttpException('A task is already active');
        }

        $lock = $request->request->all('composerLock');

        if ([] === $lock) {
            return new Response('composerLock is missing', Response::HTTP_BAD_REQUEST);
        }

        $lockContent = $this->readComposerLock($lock);
        $backupCreated = $this->writeComposerJson($request, $lock);

        try {
            Validator::createFromComposer($this->environment->getComposer(true))
                ->validate($lockContent, $this->environment->getComposerLock())
            ;
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerLock for /api/packages/cloud: '.$throwable->getMessage(), ['composerJson' => $request->request->all()['composerJson'] ?? null, 'composerLock' => $lock]);

            if ($backupCreated) {
                $this->environment->restoreBackup();
            }

            throw new BadRequestHttpException($throwable->getMessage(), $throwable);
        }

        // Only write after composer.json was validated
        $this->environment->getComposerLockFile()->write($lockContent);

        return new JsonResponse($this->taskManager->createTask('composer/install', []));
    }

    private function readComposerLock(array $lock): array
    {
        try {
            $lockFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
            $lockFile->write($lock);

            return $lockFile->read(); // Validates the JSON
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerLock for /api/packages/cloud.', ['composerLock' => $lock]);

            throw new BadRequestHttpException($throwable->getMessage(), $throwable);
        }
    }

    private function writeComposerJson(Request $request, array $lock): bool
    {
        try {
            if (!$request->request->has('composerJson') || [] === ($json = $request->request->all('composerJson'))) {
                return false;
            }

            $jsonFile = new JsonFile($this->filesystem->tempnam(sys_get_temp_dir(), md5(\Phar::running())));
            $jsonFile->write($json);
            $jsonFile->validateSchema(JsonFile::LAX_SCHEMA);

            if (!$this->isGranted('ROLE_INSTALL') && $jsonFile->read() !== $this->environment->getComposerJsonFile()->read()) {
                throw $this->createAccessDeniedException('No permission to change the composer.json');
            }

            if (!$this->environment->createBackup()) {
                $this->logger->error('Unable to create backup of composer files.', ['composerJson' => $json, 'composerLock' => $lock]);

                throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Unable to create backup of composer files.');
            }

            $this->environment->getComposerJsonFile()->write($jsonFile->read());

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Invalid composerJson for /api/packages/cloud: '.$throwable->getMessage(), ['composerJson' => $json ?? null, 'composerLock' => $lock]);

            if ($throwable instanceof HttpExceptionInterface) {
                throw $throwable;
            }

            throw new BadRequestHttpException($throwable->getMessage(), $throwable);
        }
    }
}
