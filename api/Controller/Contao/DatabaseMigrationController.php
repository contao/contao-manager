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

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\Process\ProcessController;
use Contao\ManagerApi\Task\TaskStatus;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao/database-migration", methods={"GET", "PUT", "DELETE"})
 */
class DatabaseMigrationController
{
    /**
     * @var ContaoConsole
     */
    private $console;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    public function __construct(ContaoConsole $console, ConsoleProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
        $this->console = $console;
    }

    public function __invoke(Request $request): Response
    {
        $commands = $this->console->getCommandList();

        if (
            !isset($commands['contao:migrate']['options'])
            || !\in_array('hash', $commands['contao:migrate']['options'], true)
            || !\in_array('format', $commands['contao:migrate']['options'], true)
            || !\in_array('dry-run', $commands['contao:migrate']['options'], true)
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support database migrations.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->getStatus();

            case 'PUT':
                if (null !== $this->getBackgroundProcess()) {
                    throw new BadRequestHttpException('A migration is already active');
                }

                $process = $this->createProcess(
                    $request->request->get('hash'),
                    $request->request->get('type'),
                    $request->request->getBoolean('withDeletes')
                );
                $process->setMeta(['skip_warnings' => $request->request->getBoolean('skipWarnings')]);
                $process->start();

                return new Response('', Response::HTTP_CREATED);

            case 'DELETE':
                if (null === ($process = $this->getBackgroundProcess())) {
                    return new Response();
                }

                $process->delete();

                return new Response();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function getStatus(): Response
    {
        $process = $this->getBackgroundProcess();

        if (null === $process) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $skipWarnings = (bool) ($process->getMeta()['skip_warnings'] ?? false);
        $output = trim($process->getOutput());

        if (!empty($output)) {
            $lines = explode("\n", $output);

            while ($line = array_shift($lines)) {
                $data = json_decode($line, true);
                $type = $data['type'] ?? null;

                if ('warning' === $type && $skipWarnings) {
                    continue;
                }

                if (\in_array($type, ['error', 'problem', 'warning'], true)) {
                    array_unshift($lines, $line);

                    return $this->handleProblems($lines, $process);
                }

                if ('migration-pending' === $type && !empty($data['names'])) {
                    return $this->handleMigrations($data, $lines, $process);
                }

                if ('schema-pending' === $type && !empty($data['commands'])) {
                    return $this->handleSchema($data, $lines, $process);
                }
            }
        }

        return new JsonResponse([
            'type' => $this->getProcessType($process),
            'status' => $this->getProcessStatus($process),
            'operations' => [],
            'hash' => null,
        ]);
    }

    private function createProcess(?string $hash, ?string $type, bool $withDeletes): ProcessController
    {
        $args = [
            'contao:migrate',
            '--no-interaction',
            '--format=ndjson',
            null === $hash ? '--dry-run' : '--hash='.$hash,
        ];

        switch ($type) {
            case 'migrations-only':
                $args[] = '--migrations-only';
                break;

            case 'schema-only':
                $args[] = '--schema-only';
                break;
        }

        if ($withDeletes && 'migrations-only' !== $type && null !== $hash) {
            $args[] = '--with-deletes';
        }

        return $this->processFactory->createContaoConsoleBackgroundProcess($args, 'database-migration');
    }

    private function getBackgroundProcess(): ?ProcessController
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('database-migration');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function handleProblems(array $lines, ProcessController $process): Response
    {
        $responseType = 'warning';
        $operations = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            $type = $data['type'] ?? null;
            $message = explode("\n", $data['message'] ?? '', 2) + ['', ''];

            if (\in_array($type, ['error', 'problem', 'warning'], true)) {
                if ('warning' !== $type) {
                    $responseType = 'problem';
                }

                $operations[] = [
                    'status' => 'error',
                    'name' => $message[0],
                    'message' => $message[1],
                ];
            }
        }

        return new JsonResponse([
            'type' => $responseType,
            'status' => $this->getProcessStatus($process),
            'operations' => array_values($operations),
        ]);
    }

    private function handleMigrations(array $pending, array $lines, ProcessController $process): Response
    {
        $operations = [];

        foreach ($pending['names'] as $name) {
            $operations[] = [
                'name' => $name,
                'status' => 'pending',
            ];
        }

        $c = 0;

        foreach ($lines as $line) {
            $data = json_decode($line, true);

            if ('migration-result' === ($data['type'] ?? '')) {
                $operations[$c]['message'] = $data['message'];
                $operations[$c]['status'] = ($data['isSuccessful'] ? TaskStatus::STATUS_COMPLETE : TaskStatus::STATUS_ERROR);
                ++$c;
            }
        }

        return new JsonResponse([
            'type' => $this->getProcessType($process, 'migrations'),
            'status' => $this->getProcessStatus($process),
            'operations' => array_values($operations),
            'hash' => $pending['hash'],
        ]);
    }

    private function handleSchema(array $pending, array $lines, ProcessController $process): Response
    {
        $operations = [];

        foreach ($pending['commands'] as $name) {
            $operations[$name] = [
                'name' => $name,
                'status' => !$process->isRunning() && !$process->isSuccessful() ? TaskStatus::STATUS_ERROR : 'pending',
            ];
        }

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            $type = $data['type'] ?? '';
            $name = $data['command'] ?? '';

            if ('schema-execute' === $type) {
                $operations[$name]['status'] = TaskStatus::STATUS_ACTIVE;
                continue;
            }

            if ('schema-result' === $type) {
                $operations[$name]['status'] = ($data['isSuccessful'] ? TaskStatus::STATUS_COMPLETE : TaskStatus::STATUS_ERROR);
            }
        }

        return new JsonResponse([
            'type' => $this->getProcessType($process, 'schema'),
            'status' => $this->getProcessStatus($process),
            'operations' => array_values($operations),
            'hash' => $pending['hash'],
        ]);
    }

    private function getProcessStatus(ProcessController $process): string
    {
        if (!$process->isStarted()) {
            $process->start();

            return TaskStatus::STATUS_ACTIVE;
        }

        if ($process->isRunning()) {
            return TaskStatus::STATUS_ACTIVE;
        }

        if ($process->isSuccessful()) {
            if (false !== strpos($process->getCommandLine(), '--dry-run')) {
                return 'pending';
            }

            return TaskStatus::STATUS_COMPLETE;
        }

        return TaskStatus::STATUS_ERROR;
    }

    private function getProcessType(ProcessController $process, string $default = ''): string
    {
        if (false !== strpos($process->getCommandLine(), '--schema-only')) {
            return 'schema-only';
        }

        if (false !== strpos($process->getCommandLine(), '--migrations-only')) {
            return 'migrations-only';
        }

        return $default;
    }
}
