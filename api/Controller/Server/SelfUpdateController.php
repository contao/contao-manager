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

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\System\SelfUpdate;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/self-update", methods={"GET"})
 */
class SelfUpdateController
{
    /**
     * Gets response about update status of the Contao Manager.
     */
    public function __invoke(SelfUpdate $updater): Response
    {
        if (!$updater->canUpdate()) {
            return new ApiProblemResponse(
                (new ApiProblem('This version cannot be updated.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        $error = null;

        try {
            $latestVersion = $updater->getNewVersion();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $latestVersion = $updater->getOldVersion();
        }

        return new JsonResponse(
            [
                'current_version' => $updater->getOldVersion(),
                'latest_version' => $latestVersion,
                'channel' => $updater->getChannel(),
                'supported' => $updater->supportsUpdate(),
                'error' => $error,
            ]
        );
    }
}
