<?php

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Json\JsonFile;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Task\TaskManager;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/packages/cloud", methods={"GET", "PUT"})
 */
class CloudController
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var TaskManager
     */
    private $taskManager;

    public function __construct(Environment $environment, TaskManager $taskManager)
    {
        $this->environment = $environment;
        $this->taskManager = $taskManager;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getCloudData();

            case 'PUT':
                return $this->writeAndInstall($request);
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function getCloudData(): Response
    {
        return new JsonResponse([
            'composerJson' => $this->environment->getComposerJson(),
            'composerLock' => $this->environment->getComposerLock(),
            'platform' => $this->environment->getPlatformPackages(),
            'localPackages' => $this->environment->getLocalPackages(),
        ]);
    }

    private function writeAndInstall(Request $request): Response
    {
        if (!$this->environment->useCloudResolver()) {
            return new ApiProblemResponse(
                (new ApiProblem('Composer Resolver Cloud is disabled'))
                    ->setStatus(Response::HTTP_NOT_FOUND)
            );
        }

        if ($this->taskManager->hasTask()) {
            throw new BadRequestHttpException('A task is already active');
        }

        $lock = $request->request->get('composerLock');

        if (null === $lock) {
            return new Response('composerLock is missing', Response::HTTP_BAD_REQUEST);
        }

        try {
            $lockFile = new JsonFile(tempnam(sys_get_temp_dir(), md5(\Phar::running())));
            $lockFile->write($lock);
            $lockContent = $lockFile->read(); // Validates the JSON

            if (null !== ($json = $request->request->get('composerJson'))) {
                $jsonFile = new JsonFile(tempnam(sys_get_temp_dir(), md5(\Phar::running())));
                $jsonFile->write($json);
                $jsonFile->validateSchema(JsonFile::LAX_SCHEMA);
                $this->environment->getComposerJsonFile()->write($jsonFile->read());
            }

            // Only write after composer.json was validated
            $this->environment->getComposerLockFile()->write($lockContent);
        } catch (\Throwable $exception) {
            return ApiProblemResponse::createFromException($exception);
        }

        return new JsonResponse($this->taskManager->createTask('composer/install', []));
    }
}
