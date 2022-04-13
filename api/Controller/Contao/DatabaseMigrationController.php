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

        if (!isset($commands['contao:migrate']['options'])
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

                return $this->startTask(
                    $request->request->get('hash'),
                    $request->request->get('type'),
                    $request->request->getBoolean('withDeletes')
                );

            case 'DELETE':
                if (null === ($process = $this->getBackgroundProcess())) {
                    throw new BadRequestHttpException('No migration process found.');
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

        $output = $process->getOutput();

        if (!empty($output)) {
            $lines = explode("\n", $output);

            while ($line = array_shift($lines)) {
                $data = json_decode($line, true);

                if ('migration-pending' === ($data['type'] ?? '') && !empty($data['names'])) {
                    return $this->handleMigrations($data, $lines, $process);
                }

                if ('schema-pending' === ($data['type'] ?? '') && !empty($data['commands'])) {
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

    private function startTask(?string $hash, ?string $type, bool $withDeletes): Response
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

        $process = $this->processFactory->createContaoConsoleBackgroundProcess($args, 'database-migration');

        $process->start();

        return new Response('', Response::HTTP_CREATED);
    }

    private function getBackgroundProcess(): ?ProcessController
    {
        try {
            return $this->processFactory->restoreBackgroundProcess('database-migration');
        } catch (\Exception $e) {
            return null;
        }
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
                $c++;
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
                'status' => 'pending',
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
