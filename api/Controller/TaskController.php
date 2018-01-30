<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Tenside\Task\SelfUpdateTask;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;
use Tenside\Core\Task\Composer\InstallTask;
use Tenside\Core\Task\Task;
use Tenside\Core\Task\TaskList;
use Tenside\Core\Util\JsonArray;
use Terminal42\BackgroundProcess\ProcessController;

class TaskController extends Controller
{
    const TASK_ID = 'background-process';

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var TaskList
     */
    private $taskList;

    /**
     * @var bool
     */
    private $debug;

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
     * @param bool                  $debug
     */
    public function __construct(ConsoleProcessFactory $processFactory, TaskList $taskList, $debug = false)
    {
        $this->processFactory = $processFactory;
        $this->taskList = $taskList;
        $this->debug = $debug;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getTask();

            case 'PUT':
                return $this->putTask($request);

            case 'PATCH':
                return $this->patchTask($request);

            case 'DELETE':
                return $this->deleteTask();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function getTask()
    {
        $process = $this->getActiveProcess();

        if (null === $process) {
            return new JsonResponse('', JsonResponse::HTTP_NO_CONTENT);
        }

        return $this->getJsonResponse($process);
    }

    private function putTask(Request $request)
    {
        if (null !== $this->getActiveProcess()) {
            throw new BadRequestHttpException('A task is already active');
        }

        if (!$request->request->get('type')) {
            throw new BadRequestHttpException('Invalid payload');
        }

        $metaData = new JsonArray($request->request->all());

        if ($metaData->has('debug')) {
            $metaData->set('debug', $this->debug);
        }

        if ('install' === $request->request->get('type')) {
            $metaData->set(InstallTask::SETTING_DESTINATION_DIR, $this->get('kernel')->getContaoDir());
            $metaData->set(InstallTask::SETTING_PACKAGE, 'contao/managed-edition');

            if ($version = $request->request->get('version')) {
                $metaData->set(InstallTask::SETTING_VERSION, $version);
            }
        }

        try {
            $this->taskList->queue($metaData->get('type'), $metaData, self::TASK_ID);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        $task = $this->taskList->getTask(self::TASK_ID);
        $process = $this->createAndRunProcess($task->getId(), $task instanceof SelfUpdateTask, $request->request->all());

        return $this->getJsonResponse($process, JsonResponse::HTTP_CREATED);
    }

    private function deleteTask()
    {
        if (null === ($process = $this->getActiveProcess())) {
            throw new NotFoundHttpException('No active task');
        }

        if ($process->isRunning()) {
            throw new BadRequestHttpException('Task is running and can not be deleted');
        }

        if (($task = $this->taskList->getTask($process->getId())) instanceof Task) {
            $task->removeAssets();
            $this->taskList->remove($task->getId());
        }

        $process->delete();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        if (function_exists('apc_clear_cache') && !ini_get('apc.stat')) {
            apc_clear_cache();
        }

        return $this->getJsonResponse($process);
    }

    private function patchTask(Request $request)
    {
        if (null === ($process = $this->getActiveProcess())) {
            throw new NotFoundHttpException('No active task');
        }

        $status = $request->request->get('status');

        // @deprecated: BC for self-update with version < 1.0.0-alpha6
        if ('RUNNING' === $status) {
            $status = Process::STATUS_STARTED;
        }

        switch ($status) {
            case Process::STATUS_STARTED:
                if (!$process->isRunning()) {
                    $process->start();
                }
                break;

            case Process::STATUS_TERMINATED:
                if ($process->isRunning()) {
                    $process->stop();
                }
                break;

            default:
                throw new BadRequestHttpException(sprintf('Unsupported task status "%s"', $status));
        }

        return $this->getJsonResponse($process);
    }

    private function getActiveProcess()
    {
        try {
            return $this->processFactory->restoreBackgroundProcess(self::TASK_ID);
        } catch (\InvalidArgumentException $e) {
            // Process with our ID was not found, try Tenside for BC reasons
        }

        // @deprecated BC for self-update with version < 1.0.0-alpha6
        if (($task = $this->taskList->getNext()) instanceof Task) {
            try {
                return $this->processFactory->restoreBackgroundProcess($task->getId());
            } catch (\InvalidArgumentException $e) {
                // Delete Tenside task without a process controller
//                $this->taskList->remove($task->getId());
            }
        }

        return null;
    }

    /**
     * @param ProcessController $process
     * @param int               $status
     *
     * @return JsonResponse
     */
    private function getJsonResponse(ProcessController $process, $status = JsonResponse::HTTP_OK)
    {
        $output = '';

        if (null !== ($task = $this->taskList->getTask($process->getId()))) {
            $output = $task->getOutput();

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
        }

        // @deprecated: BC for self-update with version < 1.0.0-alpha6
        $meta = $process->getMeta();
        $meta = is_array($meta) ? $meta : ['type' => 'self-update'];

        $data = array_merge(
            $meta,
            [
                'id' => $process->getId(),
                'status' => $this->getProcessStatus($process),
                'output' => $output,
            ]
        );

        return new JsonResponse($data, $status);
    }

    private function getProcessStatus(ProcessController $process)
    {
        $status = $process->getStatus();

        // @deprecated: BC for self-update with version < 1.0.0-alpha6
        if (self::TASK_ID !== $process->getId()) {
            $status = $process->isTerminated() ? Task::STATE_FINISHED : Task::STATE_RUNNING;
        }

        if ($process->isTerminated() && $process->getExitCode() > 0) {
            $status = 'error';
        }

        return $status;
    }

    private function createAndRunProcess($taskId, $disableEvents, array $meta)
    {
        $arguments = [
            'tenside:runtask',
            $taskId,
            '-v',
            '--no-interaction',
        ];

        if ($disableEvents) {
            $arguments[] = '--disable-events';
        }

        $process = $this->processFactory->createManagerConsoleBackgroundProcess(
            $arguments,
            $taskId
        );

        $process->setMeta($meta);
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
