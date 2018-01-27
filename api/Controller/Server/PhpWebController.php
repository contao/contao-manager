<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\IntegrityCheck\IntegrityCheckInterface;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PhpWebController extends Controller
{
    /**
     * Gets response about PHP web process version and issues.
     *
     * @return Response
     */
    public function __invoke()
    {
        return new JsonResponse(
            [
                'version' => PHP_VERSION,
                'version_id' => PHP_VERSION_ID,
                'platform' => $this->get('contao_manager.system.server_info')->getPlatform(),
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
        /** @var IntegrityCheckInterface[] $checks */
        $checks = [
            'contao_manager.integrity.windows',
            'contao_manager.integrity.allow_url_fopen',
            'contao_manager.integrity.intl',
            'contao_manager.integrity.openssl',
            'contao_manager.integrity.session',
            'contao_manager.integrity.memory_limit',
            'contao_manager.integrity.process',
        ];

        foreach ($checks as $check) {
            $response = $this->get($check)->run();

            if ($response instanceof ApiProblem) {
                return $response->asArray();
            }
        }

        return null;
    }
}
