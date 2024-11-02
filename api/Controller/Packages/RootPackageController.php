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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/packages/root', methods: ['GET'])]
#[IsGranted('ROLE_READ')]
class RootPackageController
{
    public function __construct(private readonly Environment $environment)
    {
    }

    public function __invoke(): Response
    {
        $dumper = new ArrayDumper();

        return new JsonResponse($dumper->dump($this->environment->getComposer()->getPackage()));
    }
}
