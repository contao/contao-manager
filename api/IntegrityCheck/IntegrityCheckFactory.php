<?php

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;

class IntegrityCheckFactory implements ServiceSubscriberInterface
{
    private static $webChecks = [
        AllowUrlFopenCheck::class,
        SysTempDirCheck::class,
        PhpExtensionsCheck::class,
        GraphicsLibCheck::class,
        SymlinkCheck::class,
        SessionCheck::class,
        MemoryLimitCheck::class,
        ProcessCheck::class,
    ];

    private static $cliChecks = [
        MemoryLimitCheck::class,
        AllowUrlFopenCheck::class,
        SysTempDirCheck::class,
        PhpExtensionsCheck::class,
        SymlinkCheck::class,
        ProcessCheck::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function runWebChecks()
    {
        return $this->runChecks(self::$webChecks);
    }

    public function runCliCheck()
    {
        return $this->runChecks(self::$cliChecks);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_unique(array_merge(self::$cliChecks, self::$webChecks));
    }

    private function runChecks(array $classes)
    {
        foreach ($classes as $class) {
            /** @var IntegrityCheckInterface $check */
            $check = $this->container->get($class);

            if (($problem = $check->run()) instanceof ApiProblem) {
                return $problem;
            }
        }

        return null;
    }
}
