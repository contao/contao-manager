<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Dumper\ArrayDumper;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/packages/root", methods={"GET"})
 */
class RootPackageController
{
    /**
     * @var Environment
     */
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function __invoke()
    {
        $dumper = new ArrayDumper();

        return new JsonResponse($dumper->dump($this->environment->getComposer()->getPackage()));
    }
}
