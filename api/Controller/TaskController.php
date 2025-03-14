<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Task\TaskManager;
use Contao\ManagerApi\Task\TaskStatus;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/task', methods: ['GET', 'PUT', 'PATCH', 'DELETE'])]
#[IsGranted('ROLE_UPDATE')]
class TaskController
{
    public function __construct(private readonly TaskManager $taskManager)
    {
    }

    public function __invoke(Request $request): Response
    {
        return match ($request->getMethod()) {
            'GET' => $this->getTask(),
            'PUT' => $this->putTask($request),
            'PATCH' => $this->patchTask($request),
            'DELETE' => $this->deleteTask(),
            default => new Response('', Response::HTTP_METHOD_NOT_ALLOWED),
        };
    }

    private function getTask(): Response
    {
        return $this->getResponse($this->taskManager->updateTask());
    }

    private function putTask(Request $request): Response
    {
        if ($this->taskManager->hasTask()) {
            throw new BadRequestHttpException('A task is already active');
        }

        $name = $request->request->get('name');
        $config = $request->request->all('config');

        if (empty($name) || !\is_array($config)) {
            throw new BadRequestHttpException('Invalid task data');
        }

        return $this->getResponse($this->taskManager->createTask($name, $config));
    }

    private function patchTask(Request $request): Response
    {
        if (!$this->taskManager->hasTask()) {
            throw new BadRequestHttpException('No active task found.');
        }

        if (TaskStatus::STATUS_ABORTING === $request->request->get('status')) {
            return $this->getResponse($this->taskManager->abortTask());
        }

        if (TaskStatus::STATUS_ACTIVE === $request->request->get('status')) {
            return $this->getResponse($this->taskManager->updateTask(true));
        }

        throw new BadRequestHttpException('Unsupported task status');
    }

    private function deleteTask(): Response
    {
        if (!$this->taskManager->hasTask()) {
            return $this->getResponse();
        }

        try {
            return $this->getResponse($this->taskManager->deleteTask());
        } catch (\RuntimeException $e) {
            return new ApiProblemResponse((new ApiProblem($e->getMessage()))->setStatus(Response::HTTP_FORBIDDEN));
        }
    }

    private function getResponse(TaskStatus|null $status = null): Response
    {
        if (!$status instanceof TaskStatus) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($status);
    }
}
