<?php

namespace Contao\ManagerApi\Controller;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Dumper\ArrayDumper;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PackagesController
{
    /**
     * @var Environment
     */
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function __invoke($name)
    {
        $composer = Factory::create(new NullIO(), $this->environment->getJsonFile(), true);

        if (empty($name)) {
            $data = $this->getLocalPackages($composer);
            $data['root'] = $this->getRootPackage($composer);

            return new JsonResponse($data);
        }

        if ('root' === $name) {
            return new JsonResponse($this->getRootPackage($composer));
        }

        $packages = $this->getLocalPackages($composer);

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
    private function getRootPackage(Composer $composer)
    {
        $dumper = new ArrayDumper();

        return $dumper->dump($composer->getPackage());
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
