<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/files/{file}", methods={"GET", "PUT"})
 */
class FileController extends Controller
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $allowedFiles = [
        'composer.json',
        'composer.lock',
    ];

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     * @param Filesystem|null $filesystem
     */
    public function __construct(KernelInterface $kernel, Filesystem $filesystem = null)
    {
        $this->kernel = $kernel;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Reads and writes a file.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        if (!in_array($request->attributes->get('file'), $this->allowedFiles, true)) {
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
