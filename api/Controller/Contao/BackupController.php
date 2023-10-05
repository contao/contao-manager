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

use Contao\ManagerApi\Exception\ProcessOutputException;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoConsole;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao/backup", methods={"GET"})
 */
class BackupController
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
            !\array_key_exists('contao:backup:list', $commands)
            || !\array_key_exists('contao:backup:create', $commands)
            || !\array_key_exists('contao:backup:restore', $commands)
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support backups.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        $arguments = ['contao:backup:list', '--format=json', '--no-interaction'];

        $process = $this->processFactory->createContaoConsoleProcess($arguments);

        $process->run();
        $data = json_decode(trim($process->getOutput()), true);

        if (!\is_array($data)) {
            throw new ProcessOutputException('Invalid response for listing backups.', $process);
        }

        return new JsonResponse($data);
    }
}
