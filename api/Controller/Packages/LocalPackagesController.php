<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Dumper\ArrayDumper;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/packages/local/{name}", methods={"GET"}, requirements={"name"=".*"})
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

    public function __invoke($name = null)
    {
        $composer = Factory::create(new NullIO(), $this->environment->getJsonFile(), true);
        $packages = $this->getLocalPackages($composer);

        if (empty($name)) {
            return new JsonResponse($packages);
        }

        if (!isset($packages[$name])) {
            throw new NotFoundHttpException('Package "'.$name.'" does not exist');
        }

        return new JsonResponse($packages[$name]);
    }

    /**
     * @param Composer $composer
     *
     * @return array
     */
    private function getLocalPackages(Composer $composer)
    {
        $packages = [];
        $dumper = new ArrayDumper();

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $packages[$package->getName()] = $dumper->dump($package);
        }

        return $packages;
    }
}
