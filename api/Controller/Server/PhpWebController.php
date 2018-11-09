<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\IntegrityCheck\IntegrityCheckInterface;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PhpWebController extends Controller
{
    /**
     * @var IntegrityCheckInterface[] $checks
     */
    private $checks;

    /**
     * @param iterable $webIntegrityChecks
     */
    public function __construct($webIntegrityChecks)
    {
        $this->checks = $webIntegrityChecks;
    }

    /**
     * Gets response about PHP web process version and issues.
     *
     * @return Response
     */
    public function __invoke(ServerInfo $serverInfo)
    {
        return new JsonResponse(
            [
                'version' => PHP_VERSION,
                'version_id' => PHP_VERSION_ID,
                'platform' => $serverInfo->getPlatform(),
                'problem' => $this->runIntegrityChecks(),
            ]
        );
    }

    /**
     * Checks system integrity and returns problem if found.
     *
     * @return array|null
     */
    private function runIntegrityChecks()
    {
        foreach ($this->checks as $check) {
            $response = $this->get($check)->run();

            if ($response instanceof ApiProblem) {
                return $response->asArray();
            }
        }

        return null;
    }
}
