<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

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

    public function runWebChecks(): ?ApiProblem
    {
        return $this->runChecks(self::$webChecks);
    }

    public function runCliCheck(): ?ApiProblem
    {
        return $this->runChecks(self::$cliChecks);
    }

    public static function getSubscribedServices(): array
    {
        return array_unique(array_merge(self::$cliChecks, self::$webChecks));
    }

    private function runChecks(array $classes): ?ApiProblem
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
