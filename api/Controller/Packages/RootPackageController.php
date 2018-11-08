<?php

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Dumper\ArrayDumper;
use Contao\ManagerApi\Composer\Environment;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        $composer = Factory::create(new NullIO(), $this->environment->getJsonFile(), true);
        $dumper = new ArrayDumper();

        return new JsonResponse($dumper->dump($composer->getPackage()));
    }
}
