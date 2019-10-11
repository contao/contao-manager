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

use Composer\Repository\ArrayRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/packages/missing", methods={"GET"})
 */
class MissingPackagesController
{
    /**
     * @var RepositoryInterface
     */
    private $localRepository;

    /**
     * @var CompositeRepository
     */
    private $compositeRepository;

    public function __construct(Environment $environment)
    {
        $composer = $environment->getComposer();

        $this->localRepository = $composer->getRepositoryManager()->getLocalRepository();

        $this->compositeRepository = new CompositeRepository(
            array(
                new ArrayRepository(array($composer->getPackage())),
                $this->localRepository,
                new PlatformRepository(array(), $composer->getConfig()->get('platform') ?: array()),
            )
        );
    }

    public function __invoke(): Response
    {
        $missing = [];

        foreach ($this->localRepository->getPackages() as $package) {
            if ($this->hasDependents([$package->getName()])) {
                continue;
            }

            $replaces = array_keys($package->getReplaces());

            if (0 !== \count($replaces) && $this->hasDependents($replaces)) {
                continue;
            }

            $missing[] = $package->getName();
        }

        if (0 === \count($missing)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($missing);
    }

    private function hasDependents(array $names): bool
    {
        return 0 !== \count($this->compositeRepository->getDependents($names, null, false, false));
    }
}
