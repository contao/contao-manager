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

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoConsole;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/contao/maintenance-mode', methods: ['GET', 'PUT', 'DELETE'])]
class MaintenanceModeController
{
    public function __construct(
        private readonly ContaoConsole $console,
        private readonly ConsoleProcessFactory $processFactory,
        private readonly ApiKernel $kernel,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $commands = $this->console->getCommandList();
        } catch (\RuntimeException) {
            $commands = [];
        }

        $hasLexik = \array_key_exists('lexik:maintenance:lock', $commands) && \array_key_exists('lexik:maintenance:unlock', $commands);

        if (!$hasLexik && !\array_key_exists('contao:maintenance-mode', $commands)) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support maintenance mode.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED),
            );
        }

        return match ($request->getMethod()) {
            'GET' => $this->getStatus($hasLexik),
            'PUT' => $this->enable($hasLexik),
            'DELETE' => $this->disable($hasLexik),
            default => new Response(null, Response::HTTP_METHOD_NOT_ALLOWED),
        };
    }

    private function getStatus(bool $lexik): Response
    {
        if ($lexik) {
            return new JsonResponse([
                'enabled' => $this->filesystem->exists($this->kernel->getProjectDir().'/var/maintenance_lock'),
            ]);
        }

        return new JsonResponse($this->runContaoCommand());
    }

    private function enable(bool $lexik): Response
    {
        if ($lexik) {
            return new JsonResponse([
                'enabled' => $this->runLexikCommand('lock'),
            ]);
        }

        return new JsonResponse($this->runContaoCommand('enable'));
    }

    private function disable(bool $lexik): Response
    {
        if ($lexik) {
            return new JsonResponse([
                'enabled' => !$this->runLexikCommand('unlock'),
            ]);
        }

        return new JsonResponse($this->runContaoCommand('disable'));
    }

    private function runContaoCommand(string|null $state = null): array
    {
        $arguments = ['contao:maintenance'];

        if (null !== $state) {
            $arguments[] = $state;
        }

        $arguments[] = '--format=json';
        $arguments[] = '--no-interaction';

        $process = $this->processFactory->createContaoConsoleProcess($arguments);

        $process->run();

        $data = json_decode(trim($process->getOutput()), true);

        if (!\is_array($data)) {
            return ['enabled' => false];
        }

        return $data;
    }

    private function runLexikCommand(string $command): bool
    {
        $process = $this->processFactory->createContaoConsoleProcess([
            'lexik:maintenance:'.$command,
            '--no-interaction',
        ]);
        $process->run();

        return $process->isSuccessful();
    }
}
