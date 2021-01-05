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
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RootPackageRepository;
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
     * @var RepositoryInterface
     */
    private $localRepository;

    /**
     * @var InstalledRepository
     */
    private $compositeRepository;

    public function __construct(Environment $environment)
    {
        $composer = $environment->getComposer();

        $this->localRepository = $composer->getRepositoryManager()->getLocalRepository();

        $this->compositeRepository = new InstalledRepository([
            new RootPackageRepository($composer->getPackage()),
            $this->localRepository,
            new PlatformRepository([], $composer->getConfig()->get('platform') ?: []),
        ]);
    }

    public function __invoke(string $name = null): Response
    {
        if (null !== $name) {
            return $this->getOnePackage($name);
        }

        $dumper = new ArrayDumper();
        $packages = [];

        foreach ($this->localRepository->getPackages() as $package) {
            $packages[$package->getName()] = $dumper->dump($package);
            $packages[$package->getName()]['dependents'] = $this->getDependents($package);
        }

        return new JsonResponse($packages);
    }

    private function getOnePackage(string $name): Response
    {
        [$package] = array_values($this->localRepository->findPackages($name));

        if (!$package instanceof PackageInterface) {
            throw new NotFoundHttpException('Package "'.$name.'" is not installed');
        }

        $dumper = new ArrayDumper();

        $data = $dumper->dump($package);
        $data['dependents'] = $this->getDependents($package);

        return new JsonResponse($data);
    }

    private function getDependents(PackageInterface $package): array
    {
        $dependents = $this->parseDependents([$package->getName()]);

        if (empty($dependents) && 0 !== \count($replaces = array_keys($package->getReplaces()))) {
            $dependents = $this->parseDependents($replaces, true);
        }

        return $dependents;
    }

    private function parseDependents(array $packageNames, bool $withReplaces = false): array
    {
        $links = [];
        $dependents = $this->compositeRepository->getDependents($packageNames, null, false, false);

        foreach ($dependents as $dependent) {
            /** @var Link $link */
            [, $link] = $dependent;

            if (!$withReplaces && 'replaces' === $link->getDescription()) {
                continue;
            }

            $constraint = $link->getConstraint();

            $links[] = [
                'description' => $link->getDescription(),
                'source' => $link->getSource(),
                'target' => $link->getTarget(),
                'constraint' => $constraint ? $constraint->getPrettyString() : null,
            ];
        }

        return $links;
    }
}
