<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Package\Dumper\ArrayDumper;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/packages/local", methods={"GET"})
 * @Route("/packages/local/{name}", methods={"GET"}, requirements={"name"=".+"})
 */
class LocalPackagesController
{
    /**
     * @var Environment
     */
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function __invoke(string $name = null): Response
    {
        $packages = $this->getLocalPackages();

        if (empty($name)) {
            return new JsonResponse($packages);
        }

        if (!isset($packages[$name])) {
            throw new NotFoundHttpException('Package "'.$name.'" does not exist');
        }

        return new JsonResponse($packages[$name]);
    }

    private function getLocalPackages(): array
    {
        $packages = [];
        $dumper = new ArrayDumper();
        $repository = $this->environment
            ->getComposer()
            ->getRepositoryManager()
            ->getLocalRepository()
        ;

        foreach ($repository->getPackages() as $package) {
            $packages[$package->getName()] = $dumper->dump($package);
        }

        return $packages;
    }
}
