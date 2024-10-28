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
    private static array $webChecks = [
        AllowUrlFopenCheck::class,
        SysTempDirCheck::class,
        PhpExtensionsCheck::class,
        GraphicsLibCheck::class,
        SymlinkCheck::class,
        SessionCheck::class,
        MemoryLimitCheck::class,
        ProcessCheck::class,
    ];

    private static array $cliChecks = [
        MemoryLimitCheck::class,
        AllowUrlFopenCheck::class,
        SysTempDirCheck::class,
        PhpExtensionsCheck::class,
        SymlinkCheck::class,
        ProcessCheck::class,
    ];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function runWebChecks(): ApiProblem|null
    {
        return $this->runChecks(self::$webChecks);
    }

    public function runCliCheck(): ApiProblem|null
    {
        return $this->runChecks(self::$cliChecks);
    }

    public static function getSubscribedServices(): array
    {
        return array_unique(array_merge(self::$cliChecks, self::$webChecks));
    }

    private function runChecks(array $classes): ApiProblem|null
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
