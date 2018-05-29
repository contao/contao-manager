<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\Server;

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

        if (!$updater->canUpdate()) {
            return new ApiProblemResponse(
                (new ApiProblem('This version cannot be updated.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        try {
            $latestVersion = $updater->getNewVersion();
        } catch (\Exception $e) {
            $latestVersion = $updater->getOldVersion();
        }

        return new JsonResponse(
            [
                'current_version' => $updater->getOldVersion(),
                'latest_version' => $latestVersion,
                'channel' => $updater->getChannel(),
            ]
        );
    }
}
