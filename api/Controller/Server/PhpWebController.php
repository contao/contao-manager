<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\IntegrityCheck\IntegrityCheckFactory;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/server/php-web', methods: ['GET'])]
class PhpWebController
{
    public function __construct(private readonly IntegrityCheckFactory $integrity)
    {
    }

    /**
     * Gets response about PHP web process version and issues.
     */
    public function __invoke(ServerInfo $serverInfo): Response
    {
        return new JsonResponse(
            [
                'version' => PHP_VERSION,
                'version_id' => \PHP_VERSION_ID,
                'platform' => $serverInfo->getPlatform(),
                'problem' => $this->runIntegrityChecks(),
            ],
        );
    }

    /**
     * Checks system integrity and returns problem if found.
     */
    private function runIntegrityChecks(): array|null
    {
        $problem = $this->integrity->runWebChecks();

        if ($problem instanceof ApiProblem) {
            return $problem->asArray();
        }

        return null;
    }
}
