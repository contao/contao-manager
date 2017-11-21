<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\System;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SelfUpdateController extends Controller
{
    /**
     * Gets response about update status of the Contao Manager.
     *
     * @return Response
     */
    public function __invoke()
    {
        $updater = $this->get('contao_manager.self_update.updater');
        $managerConfig = $this->get('contao_manager.config.manager');

        if (!$updater->canUpdate()) {
            return new ApiProblemResponse(
                (new ApiProblem('This version cannot be updated.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        if ($managerConfig->has('last_update')
            && false !== ($lastUpdate = new \DateTime($managerConfig->get('last_update')))
            && $lastUpdate <= time()
            && $lastUpdate > new \DateTime('-1 hour')
        ) {
            return $this->createResponse(false);
        }

        $managerConfig->set('last_update', (new \DateTime())->format('c'));

        try {
            return $this->createResponse($updater->hasUpdate());
        } catch (\Exception $e) {
            return $this->createResponse(false);
        }
    }

    /**
     * Creates a JSON response about the update status.
     *
     * @param bool $hasUpdate
     *
     * @return JsonResponse
     */
    private function createResponse($hasUpdate)
    {
        $updater = $this->get('contao_manager.self_update.updater');

        return new JsonResponse(
            [
                'has_update' => $hasUpdate,
                'last_update' => $this->get('contao_manager.config.manager')->get('last_update'),
                'current_version' => $updater->getOldVersion(),
                'latest_version' => $updater->getNewVersion(),
            ]
        );
    }
}
