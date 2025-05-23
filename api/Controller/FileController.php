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
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/files/{file}', methods: ['GET', 'PUT'])]
#[IsGranted('ROLE_ADMIN')]
class FileController
{
    private const ALLOWED_FILES = [
        'composer.json',
        'composer.lock',
    ];

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!\in_array($request->attributes->get('file'), self::ALLOWED_FILES, true)) {
            return new ApiProblemResponse((new ApiProblem())->setStatus(Response::HTTP_FORBIDDEN));
        }

        $file = $this->kernel->getProjectDir().'/'.$request->attributes->get('file');

        if ($request->isMethod('PUT')) {
            $this->filesystem->dumpFile($file, $request->getContent());
        } elseif (!$this->filesystem->exists($file)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new Response(file_get_contents($file));
    }
}
