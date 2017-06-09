<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Tenside\Task\SelfUpdateTask;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tenside\Core\Task\Task;
use Tenside\Core\Task\TaskList;
use Tenside\Core\Util\JsonArray;
use Terminal42\BackgroundProcess\ProcessController;

class TaskController
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var TaskList
     */
    private $taskList;

    private static $signals = [
        1 => 'SIGHUP',
        2 => 'SIGINT',
        3 => 'SIGQUIT',
        15 => 'SIGTERM',
        9 => 'SIGKILL',
    ];

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param TaskList              $taskList
     */
    public function __construct(ConsoleProcessFactory $processFactory, TaskList $taskList)
    {
        $this->processFactory = $processFactory;
        $this->taskList = $taskList;
    }

    public function getTask()
    {
        $task = $this->taskList->getNext();

        if (!$task instanceof Task) {
            return new JsonResponse('', JsonResponse::HTTP_NO_CONTENT);
        }

        return new JsonResponse($this->describeTask($task));
    }

    public function putTask(Request $request)
    {
        if (null !== $this->taskList->getNext()) {
            throw new BadRequestHttpException('A task is already active');
        }

        $metaData = null;
        $content = $request->getContent();
        if (empty($content)) {
            throw new BadRequestHttpException('Invalid payload');
        }
        $metaData = new JsonArray($content);
        if (!$metaData->has('type')) {
            throw new BadRequestHttpException('Invalid payload');
        }

        try {
            $this->taskList->queue($metaData->get('type'), $metaData);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        $task = $this->taskList->getNext();
        $process = $this->startTask($task);

        return new JsonResponse(
            $this->describeTask($task, $process),
            JsonResponse::HTTP_CREATED
        );
    }

    public function deleteTask()
    {
        $task = $this->getCurrentTask();

        $process = $this->processFactory->restoreBackgroundProcess($task->getId());

        if ($this->getTaskStatus($task, $process) === Task::STATE_RUNNING) {
            throw new BadRequestHttpException('Task is running and can not be deleted');
        }

        $task->removeAssets();
        $this->taskList->remove($task->getId());
        $process->delete();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        if (function_exists('apc_clear_cache') && !ini_get('apc.stat')) {
            apc_clear_cache();
        }

        return new JsonResponse($this->describeTask($task, $process));
    }

    public function putTaskStatus(Request $request)
    {
        $task = $this->getCurrentTask();
        $status = $request->request->get('status');

        switch ($status) {
            case Task::STATE_RUNNING:
                if (Task::STATE_RUNNING !== $this->getTaskStatus($task)) {
                    $this->startTask($task);
                }
                break;

            case 'STOPPED':
                if (Task::STATE_RUNNING === $this->getTaskStatus($task)) {
                    $process = $this->processFactory->restoreBackgroundProcess($task->getId());
                    $process->stop();
                }
                break;

            default:
                throw new BadRequestHttpException(sprintf('Unsupported task status "%s"', $status));
        }

        return new JsonResponse(['status' => $task->getStatus()]);
    }

    private function getCurrentTask()
    {
        $task = $this->taskList->getNext();

        if (!$task instanceof Task) {
            throw new NotFoundHttpException('No active task');
        }

        return $task;
    }

    private function describeTask(Task $task, ProcessController $process = null)
    {
        $output = $task->getOutput();

        try {
            if (null === $process) {
                $process = $this->processFactory->restoreBackgroundProcess($task->getId());
            }

            if ($out = $process->getOutput()) {
                $output .= "\n\n".$out;
            }

            if ($err = $process->getErrorOutput()) {
                $output .= "\n\n".$err;
            }

            if ($process->isTerminated() && ($exitCode = $process->getExitCode()) > 0) {
                $output .= sprintf(
                    "\n\nProcess terminated with exit code %s\nReason: %s",
                    $exitCode,
                    $process->getExitCodeText()
                );

                if ($process->hasBeenSignaled()) {
                    $output .= $this->getSignalText($process->getTermSignal());
                } elseif ($process->hasBeenStopped()) {
                    $output .= $this->getSignalText($process->getStopSignal());
                }
            }
        } catch (\InvalidArgumentException $e) {
            // Process file not found
        }

        $data = [
            'id' => $task->getId(),
            'status' => $this->getTaskStatus($task),
            'type' => $task->getType(),
            'created_at' => $task->getCreatedAt()->format(\DateTime::ISO8601),
            'output' => $output,
        ];

        return $data;
    }

    private function getTaskStatus(Task $task, ProcessController $process = null)
    {
        $status = $task->getStatus();

        if (Task::STATE_RUNNING === $status) {
            try {
                if (null === $process) {
                    $process = $this->processFactory->restoreBackgroundProcess($task->getId());
                }

                if ($process->isTerminated()) {
                    $status = Task::STATE_ERROR;
                }
            } catch (\InvalidArgumentException $e) {
                // Process file not found
            }
        }

        return $status;
    }

    private function startTask(Task $task)
    {
        $arguments = [
            'tenside:runtask',
            $task->getId(),
            '-v',
            '--no-interaction',
        ];

        if ($task instanceof SelfUpdateTask) {
            $arguments[] = '--disable-events';
        }

        $process = $this->processFactory->createManagerConsoleBackgroundProcess(
            $arguments,
            $task->getId()
        );

        $process->setTimeout(0);
        $process->start();

        return $process;
    }

    private function getSignalText($signal)
    {
        if (isset(static::$signals[$signal])) {
            return sprintf(' [%s]', static::$signals[$signal]);
        }

        return sprintf(' [signal %s]', $signal);
    }
}
