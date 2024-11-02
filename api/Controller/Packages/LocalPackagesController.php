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
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RootPackageRepository;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/packages/local', methods: ['GET'])]
#[Route(path: '/packages/local/{name}', methods: ['GET'], requirements: ['name' => '.+'])]
#[IsGranted('ROLE_READ')]
class LocalPackagesController
{
    private readonly InstalledRepositoryInterface $localRepository;

    private readonly InstalledRepository $compositeRepository;

    public function __construct(private readonly Environment $environment)
    {
        $composer = $this->environment->getComposer();

        $this->localRepository = $composer->getRepositoryManager()->getLocalRepository();

        $this->compositeRepository = new InstalledRepository([
            new RootPackageRepository($composer->getPackage()),
            $this->localRepository,
            new PlatformRepository([], $composer->getConfig()->get('platform') ?: []),
        ]);
    }

    public function __invoke(Request $request, string|null $name = null): Response
    {
        if (null !== $name) {
            return $this->getOnePackage($name, $request->getPreferredLanguage());
        }

        $dumper = new ArrayDumper();
        $packages = [];

        foreach ($this->localRepository->getPackages() as $package) {
            $packages[$package->getName()] = $this->environment->mergeMetadata($dumper->dump($package), $request->getPreferredLanguage());
            $packages[$package->getName()]['dependents'] = $this->getDependents($package);
        }

        return new JsonResponse($packages);
    }

    private function getOnePackage(string $name, string|null $language = null): Response
    {
        [$package] = array_values($this->localRepository->findPackages($name));

        if (!$package instanceof PackageInterface) {
            throw new NotFoundHttpException('Package "'.$name.'" is not installed');
        }

        $dumper = new ArrayDumper();

        $data = $this->environment->mergeMetadata($dumper->dump($package), $language);
        $data['dependents'] = $this->getDependents($package);

        return new JsonResponse($data);
    }

    private function getDependents(PackageInterface $package): array
    {
        $dependents = $this->parseDependents([$package->getName()]);

        if ([] === $dependents && [] !== ($replaces = array_keys($package->getReplaces()))) {
            return $this->parseDependents($replaces, true);
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
                'constraint' => $constraint->getPrettyString(),
            ];
        }

        return $links;
    }
}
